<?php

namespace Tests\Feature;

use App\Models\Reminder;
use App\Models\User;
use App\Models\WhatsAppIdentity;
use App\Services\Vault\VaultService;
use App\Services\WhatsApp\WhatsAppMessageProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('whatsapp_verify:1');
    }

    public function test_security_headers_are_present_on_all_responses(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
    }

    public function test_public_registration_cannot_privilege_escalate_to_super_admin(): void
    {
        $response = $this->post('/register', [
            'name' => 'Attacker User',
            'email' => 'attacker@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => 'super_admin', // Attempted privilege escalation
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'attacker@example.com',
            'role' => 'customer', // Must be strictly customer
        ]);

        $attacker = User::where('email', 'attacker@example.com')->first();
        $this->assertFalse($attacker->isSuperAdmin());
    }

    public function test_non_admin_cannot_access_super_admin_endpoints(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->get('/admin');
        $response->assertStatus(403);

        $responseTools = $this->actingAs($customer)->get('/admin/whatsapp');
        $responseTools->assertStatus(403);

        $responseAudit = $this->actingAs($customer)->get('/admin/audit-logs');
        $responseAudit->assertStatus(403);
    }

    public function test_customer_isolation_prevents_idor_on_reminders(): void
    {
        $customerA = User::factory()->create();
        $customerB = User::factory()->create();

        $reminderA = Reminder::create([
            'user_id' => $customerA->id,
            'title' => 'Customer A Private Doctor Visit',
            'due_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        // Customer B tries to edit Customer A's reminder
        $responseEdit = $this->actingAs($customerB)->get(route('reminders.edit', $reminderA));
        $responseEdit->assertStatus(403);

        // Customer B tries to update Customer A's reminder
        $responseUpdate = $this->actingAs($customerB)->put(route('reminders.update', $reminderA), [
            'title' => 'Maliciously Altered Title',
            'date' => now()->addDays(2)->toDateString(),
            'time' => '10:00',
        ]);
        $responseUpdate->assertStatus(403);

        // Customer B tries to complete Customer A's reminder
        $responseComplete = $this->actingAs($customerB)->post(route('reminders.complete', $reminderA));
        $responseComplete->assertStatus(403);

        // Customer B tries to delete Customer A's reminder
        $responseDelete = $this->actingAs($customerB)->delete(route('reminders.destroy', $reminderA));
        $responseDelete->assertStatus(403);

        // Verify title did not change
        $this->assertEquals('Customer A Private Doctor Visit', $reminderA->fresh()->title);
    }

    public function test_vault_strictly_rejects_disguised_executable_scripts(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $vaultService = app(VaultService::class);

        // Disguised PHP script with image/jpeg MIME header claim
        $maliciousPhp = "<?php system(\$_GET['cmd']); ?>\xFF\xD8\xFF";
        $filePhp = $vaultService->storeMedia($user, $maliciousPhp, 'image/jpeg', 'exploit.php.jpg');
        $this->assertNull($filePhp);

        // SVG vector with embedded script disguised as image/jpeg
        $maliciousSvg = "<svg onload=alert(1)>fake content";
        $fileSvg = $vaultService->storeMedia($user, $maliciousSvg, 'image/jpeg', 'xss.jpg');
        $this->assertNull($fileSvg);

        // Valid JPEG file with authentic signature
        $validJpeg = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01ValidBinaryImageData";
        $fileValid = $vaultService->storeMedia($user, $validJpeg, 'image/jpeg', 'receipt.jpg');
        $this->assertNotNull($fileValid);
    }

    public function test_whatsapp_otp_verification_locks_after_excessive_failed_attempts(): void
    {
        $user = User::factory()->create();
        $identity = WhatsAppIdentity::create([
            'user_id' => $user->id,
            'phone_number' => '8801700000099',
            'status' => 'pending_verification',
            'verification_token' => 'SECRET7',
            'verification_expires_at' => now()->addHour(),
        ]);

        $processor = app(WhatsAppMessageProcessor::class);

        // Submit 5 wrong OTPs
        for ($i = 1; $i <= 5; $i++) {
            $processor->processInbound([
                'id' => 'wamid_fail_' . $i,
                'from' => '8801700000099',
                'type' => 'text',
                'text' => ['body' => 'WRONG' . $i],
            ], []);
        }

        // Identity must remain pending_verification
        $this->assertEquals('pending_verification', $identity->fresh()->status);

        // Verify throttle lock is now active
        $throttleKey = 'whatsapp_verify:' . $identity->id;
        $this->assertTrue(RateLimiter::tooManyAttempts($throttleKey, 5));

        // 6th attempt should be blocked by rate limiter even if it provides the correct code!
        $processor->processInbound([
            'id' => 'wamid_fail_6_correct_token',
            'from' => '8801700000099',
            'type' => 'text',
            'text' => ['body' => 'SECRET7'],
        ], []);

        // Account must STILL be pending because it was locked!
        $this->assertEquals('pending_verification', $identity->fresh()->status);
    }
}
