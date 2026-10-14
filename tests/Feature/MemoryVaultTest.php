<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VaultFile;
use App\Services\Vault\VaultService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemoryVaultTest extends TestCase
{
    use RefreshDatabase;

    protected VaultService $vaultService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->vaultService = app(VaultService::class);
    }

    public function test_user_can_store_supported_image(): void
    {
        $user = User::factory()->create();
        $fakeImageContent = "\xFF\xD8\xFF\xE0\x00\x10JFIF" . 'FAKE_JPEG_BINARY_DATA_123';

        $file = $this->vaultService->storeMedia(
            $user,
            $fakeImageContent,
            'image/jpeg',
            'product_earbuds.jpg',
            'X89 Earbuds Photo #tech'
        );

        $this->assertNotNull($file);
        $this->assertEquals($user->id, $file->user_id);
        $this->assertEquals('image/jpeg', $file->mime_type);
        $this->assertEquals(hash('sha256', $fakeImageContent), $file->checksum_sha256);
        $this->assertTrue($file->isImage());
        $this->assertFalse($file->isPdf());

        // Assert file stored under opaque path on private local disk
        Storage::disk('local')->assertExists($file->storage_path);
        $this->assertStringStartsWith("vault/{$user->id}/", $file->storage_path);
        $this->assertStringEndsWith('.jpg', $file->storage_path);
    }

    public function test_user_can_store_pdf_document(): void
    {
        $user = User::factory()->create();
        $fakePdfContent = '%PDF-1.4 Fake invoice content';

        $file = $this->vaultService->storeMedia(
            $user,
            $fakePdfContent,
            'application/pdf',
            'invoice_march.pdf',
            'March 2026 Internet Bill'
        );

        $this->assertNotNull($file);
        $this->assertTrue($file->isPdf());
        $this->assertEquals('finance', $file->category);
        Storage::disk('local')->assertExists($file->storage_path);
    }

    public function test_unsupported_mime_type_is_rejected(): void
    {
        $user = User::factory()->create();
        $fakeExecutable = 'MZ\x90\x00\x03Executable';

        $file = $this->vaultService->storeMedia(
            $user,
            $fakeExecutable,
            'application/x-msdownload',
            'malware.exe'
        );

        $this->assertNull($file);
        $this->assertEquals(0, VaultFile::count());
    }

    public function test_disguised_script_in_image_is_rejected(): void
    {
        $user = User::factory()->create();
        $fakeMalicious = "<?php phpinfo(); ?>\xFF\xD8\xFF";

        $file = $this->vaultService->storeMedia(
            $user,
            $fakeMalicious,
            'image/jpeg',
            'exploit.jpg'
        );

        $this->assertNull($file);
        $this->assertEquals(0, VaultFile::count());
    }

    public function test_natural_language_search_finds_file_by_topic(): void
    {
        $user = User::factory()->create();

        $file1 = $this->vaultService->storeMedia(
            $user,
            "\xFF\xD8\xFF" . 'binary1',
            'image/jpeg',
            'IMG_001.jpg',
            'Photo of Sony WH-1000XM5 headphones'
        );

        $file2 = $this->vaultService->storeMedia(
            $user,
            "%PDF-1.4 " . 'binary2',
            'application/pdf',
            'tax_certificate_2025.pdf',
            'Government Tax Return'
        );

        // Search for headphones
        $results = $this->vaultService->searchFiles($user, 'Sony headphones');
        $this->assertCount(1, $results);
        $this->assertEquals($file1->id, $results->first()->id);

        // Search for invoice/tax PDF
        $pdfResults = $this->vaultService->searchFiles($user, 'Tax', fileType: 'pdf');
        $this->assertCount(1, $pdfResults);
        $this->assertEquals($file2->id, $pdfResults->first()->id);
    }

    public function test_user_cannot_access_another_users_vault_files(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $fileA = $this->vaultService->storeMedia(
            $userA,
            "\x89PNG\r\n\x1a\n" . 'secretA',
            'image/png',
            'passport_scan.png',
            'National ID Scan'
        );

        // User B searching should find 0 results
        $results = $this->vaultService->searchFiles($userB, 'passport');
        $this->assertCount(0, $results);

        // User B attempting to get physical path directly gets null
        $path = $this->vaultService->getFilePathForUser($userB, $fileA->id);
        $this->assertNull($path);
    }
}
