<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false; // only created_at is used

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'reason',
        'ip_address',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an audit log entry with automatic actor, IP, and sanitized metadata.
     */
    public static function record(
        string $action,
        mixed $target = null,
        ?string $reason = null,
        ?array $metadata = null,
        ?int $userId = null
    ): self {
        $actorId = $userId ?? Auth::id();

        // Target type and target id extraction
        $targetType = null;
        $targetId = null;

        if ($target instanceof Model) {
            $targetType = class_basename($target);
            $targetId = $target->getKey();
        } elseif (is_string($target)) {
            $targetType = $target;
        }

        // Sanitize metadata to never include sensitive values
        $safeMetadata = self::sanitizeMetadata($metadata);

        return self::create([
            'user_id' => $actorId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'reason' => $reason,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'metadata' => $safeMetadata,
            'created_at' => now(),
        ]);
    }

    /**
     * Redact sensitive keys from metadata array.
     */
    protected static function sanitizeMetadata(?array $data): ?array
    {
        if (!$data) {
            return null;
        }

        $sensitivePatterns = ['password', 'token', 'secret', 'key', 'auth', 'otp', 'verification_code'];

        $sanitized = [];
        foreach ($data as $key => $value) {
            $keyLower = strtolower((string)$key);
            $isSensitive = false;
            foreach ($sensitivePatterns as $pattern) {
                if (str_contains($keyLower, $pattern)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeMetadata($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
