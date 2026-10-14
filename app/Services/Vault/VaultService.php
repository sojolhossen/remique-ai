<?php

namespace App\Services\Vault;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\VaultFile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VaultService
{
    public const ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    protected int $maxFileSizeBytes;

    public function __construct()
    {
        $maxMb = (int) config('whatsapp.max_file_size_mb', 15);
        $this->maxFileSizeBytes = $maxMb * 1024 * 1024;
    }

    /**
     * Store inbound binary media into user's private Memory Vault.
     */
    public function storeMedia(
        User $user,
        string $binaryContents,
        string $mimeType,
        ?string $originalFilename = null,
        ?string $caption = null,
        ?int $whatsappMessageId = null
    ): ?VaultFile {
        $mimeType = strtolower(trim($mimeType));

        // 1. Validate MIME type
        if (!array_key_exists($mimeType, self::ALLOWED_MIME_TYPES)) {
            Log::warning("Vault rejected unsupported MIME type: {$mimeType} for user {$user->id}");
            return null;
        }

        // 2. Validate file size
        $fileSize = strlen($binaryContents);
        if ($fileSize <= 0 || $fileSize > $this->maxFileSizeBytes) {
            Log::warning("Vault rejected file size: {$fileSize} bytes for user {$user->id}");
            return null;
        }

        // 3. Validate magic bytes / file signature to prevent MIME spoofing
        if (! $this->validateMagicBytes($binaryContents, $mimeType)) {
            Log::warning("Vault rejected file with invalid magic bytes for MIME {$mimeType} for user {$user->id}");
            return null;
        }

        // 4. Compute SHA-256 checksum
        $checksum = hash('sha256', $binaryContents);

        // 5. Generate opaque storage key (never use client-supplied filename for filesystem path)
        $extension = self::ALLOWED_MIME_TYPES[$mimeType];
        $opaqueFilename = Str::uuid()->toString() . '.' . $extension;
        $relativePath = "vault/{$user->id}/{$opaqueFilename}";

        // 6. Clean original filename for display (strictly strip path traversal, null bytes, and non-printable characters)
        $cleanOriginalName = $originalFilename
            ? basename(str_replace(["\0", '..', '/', '\\'], '', preg_replace('/[^a-zA-Z0-9_\-\. ]/', '', $originalFilename)))
            : "file_{$opaqueFilename}";

        try {
            // Write to private local disk
            Storage::disk('local')->put($relativePath, $binaryContents);

            // Extract initial search text from caption and filename
            $searchText = trim(($caption ?? '') . ' ' . pathinfo($cleanOriginalName, PATHINFO_FILENAME));

            $vaultFile = VaultFile::create([
                'user_id' => $user->id,
                'whatsapp_message_id' => $whatsappMessageId,
                'storage_disk' => 'local',
                'storage_path' => $relativePath,
                'original_filename' => $cleanOriginalName,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'checksum_sha256' => $checksum,
                'caption' => $caption,
                'category' => $this->detectCategory($mimeType, $caption),
                'tags' => $this->extractTags($caption),
                'search_text' => $searchText,
                'status' => 'active',
            ]);

            AuditLog::record(
                'vault.file_uploaded',
                $vaultFile,
                'User stored file into personal memory vault',
                [
                    'mime_type' => $mimeType,
                    'file_size' => $fileSize,
                    'checksum' => $checksum,
                ],
                $user->id
            );

            return $vaultFile;
        } catch (\Throwable $e) {
            Log::error("Failed to persist vault file for user {$user->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Search user's vault files using natural language query terms.
     */
    public function searchFiles(User $user, string $query, ?string $fileType = null, int $limit = 5): Collection
    {
        $cleanQuery = trim($query);

        $qb = VaultFile::query()
            ->forUser($user->id)
            ->active();

        if ($fileType) {
            if ($fileType === 'pdf' || $fileType === 'document') {
                $qb->where('mime_type', 'application/pdf');
            } elseif ($fileType === 'image') {
                $qb->where('mime_type', 'like', 'image/%');
            }
        }

        if (!empty($cleanQuery)) {
            // Tokenize query words
            $terms = array_filter(explode(' ', strtolower($cleanQuery)), fn($t) => strlen($t) >= 2);

            $qb->where(function ($sub) use ($terms, $cleanQuery) {
                $sub->where('original_filename', 'like', "%{$cleanQuery}%")
                    ->orWhere('caption', 'like', "%{$cleanQuery}%")
                    ->orWhere('search_text', 'like', "%{$cleanQuery}%");

                foreach ($terms as $term) {
                    $sub->orWhere('search_text', 'like', "%{$term}%")
                        ->orWhere('category', 'like', "%{$term}%");
                }
            });
        }

        return $qb->orderByDesc('created_at')->limit($limit)->get();
    }

    /**
     * Get absolute path for file sending, verifying ownership.
     */
    public function getFilePathForUser(User $user, int $fileId): ?string
    {
        $file = VaultFile::forUser($user->id)->active()->find($fileId);
        if (!$file) {
            return null;
        }

        $fullPath = Storage::disk('local')->path($file->storage_path);
        if (!file_exists($fullPath)) {
            Log::error("Vault physical file missing at {$fullPath} for record {$fileId}");
            return null;
        }

        return $fullPath;
    }

    /**
     * Soft-delete vault file.
     */
    public function deleteFile(User $user, int $fileId): bool
    {
        $file = VaultFile::forUser($user->id)->active()->find($fileId);
        if (!$file) {
            return false;
        }

        $file->status = 'deleted';
        $file->deleted_at = now();
        $saved = $file->save();

        if ($saved) {
            AuditLog::record(
                'vault.file_deleted',
                $file,
                'File deleted by user',
                ['file_id' => $fileId],
                $user->id
            );
        }

        return $saved;
    }

    /**
     * Get total storage used by user in bytes.
     */
    public function getUserStorageUsage(User $user): int
    {
        return (int) VaultFile::forUser($user->id)->active()->sum('file_size');
    }

    /**
     * Get global vault storage metrics for Super Admin.
     */
    public function getGlobalStorageMetrics(): array
    {
        $totalBytes = (int) VaultFile::active()->sum('file_size');
        $totalCount = VaultFile::active()->count();
        $imagesCount = VaultFile::active()->where('mime_type', 'like', 'image/%')->count();
        $docsCount = VaultFile::active()->where('mime_type', 'application/pdf')->count();

        return [
            'total_bytes' => $totalBytes,
            'total_count' => $totalCount,
            'images_count' => $imagesCount,
            'documents_count' => $docsCount,
            'formatted_size' => $this->formatBytes($totalBytes),
        ];
    }

    protected function detectCategory(string $mimeType, ?string $caption): string
    {
        $captionLower = strtolower($caption ?? '');
        if (str_contains($captionLower, 'invoice') || str_contains($captionLower, 'bill') || str_contains($captionLower, 'receipt')) {
            return 'finance';
        }
        if (str_contains($captionLower, 'nid') || str_contains($captionLower, 'passport') || str_contains($captionLower, 'id')) {
            return 'identity';
        }
        if (str_contains($captionLower, 'product') || str_contains($captionLower, 'earbuds') || str_contains($captionLower, 'watch')) {
            return 'product';
        }

        return str_starts_with($mimeType, 'image/') ? 'photo' : 'document';
    }

    protected function extractTags(?string $caption): array
    {
        if (empty($caption)) {
            return [];
        }

        preg_match_all('/#(\w+)/', $caption, $matches);
        if (!empty($matches[1])) {
            return array_map('strtolower', $matches[1]);
        }

        return [];
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }

    /**
     * Validate that binary file contents match authentic magic byte signatures.
     */
    public function validateMagicBytes(string $binaryContents, string $mimeType): bool
    {
        if (strlen($binaryContents) < 4) {
            return false;
        }

        // Strictly reject embedded executable, PHP, HTML script, or SVG vector payload markers
        $lowerPrefix = strtolower(substr($binaryContents, 0, 512));
        if (
            str_contains($lowerPrefix, '<?php') ||
            str_contains($lowerPrefix, '<?=') ||
            str_contains($lowerPrefix, '<script') ||
            str_contains($lowerPrefix, '<svg') ||
            str_contains($lowerPrefix, '<html')
        ) {
            Log::warning("Vault rejected payload containing executable script or SVG markers.");
            return false;
        }

        return match ($mimeType) {
            'image/jpeg' => str_starts_with($binaryContents, "\xFF\xD8\xFF"),
            'image/png' => str_starts_with($binaryContents, "\x89PNG\r\n\x1a\n") || str_starts_with($binaryContents, "\x89PNG"),
            'image/webp' => str_starts_with($binaryContents, "RIFF") && substr($binaryContents, 8, 4) === "WEBP",
            'application/pdf' => str_starts_with($binaryContents, "%PDF-"),
            default => false,
        };
    }
}

