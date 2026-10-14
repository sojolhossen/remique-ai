<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Reminder;
use App\Models\User;
use App\Models\WhatsAppIdentity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => 'super_admin',
            'email' => 'admin@remique.test',
        ]);

        $this->regularCustomer = User::factory()->create([
            'role' => 'customer',
            'email' => 'customer@remique.test',
        ]);
    }

    public function test_guest_is_redirected_to_login_from_admin_console(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_regular_customer_is_forbidden_from_admin_console(): void
    {
        $response = $this->actingAs($this->regularCustomer)->get('/admin');
        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_overview_dashboard(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('Enterprise Operations Overview');
        $response->assertSee('Authorized Customers');
    }

    public function test_super_admin_can_view_and_filter_customers(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/customers');

        $response->assertStatus(200);
        $response->assertSee($this->regularCustomer->name);
    }

    public function test_super_admin_can_link_whatsapp_number_and_generate_otp(): void
    {
        $response = $this->actingAs($this->superAdmin)->post("/admin/customers/{$this->regularCustomer->id}/link-whatsapp", [
            'phone_number' => '01712345678',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_identities', [
            'user_id' => $this->regularCustomer->id,
            'phone_number' => '+8801712345678', // E.164 normalized
            'status' => 'pending_verification',
        ]);

        // Assert audit log was recorded
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => 'whatsapp.linking_initiated',
        ]);
    }

    public function test_super_admin_can_manually_verify_whatsapp_with_required_reason(): void
    {
        $identity = WhatsAppIdentity::create([
            'user_id' => $this->regularCustomer->id,
            'phone_number' => '+8801755555555',
            'status' => 'pending_verification',
            'verification_token' => 'ABC123',
        ]);

        // Attempt without reason fails validation
        $failResponse = $this->actingAs($this->superAdmin)->post("/admin/identities/{$identity->id}/manual-verify", [
            'reason' => '',
        ]);
        $failResponse->assertSessionHasErrors('reason');

        // Attempt with valid reason succeeds
        $successResponse = $this->actingAs($this->superAdmin)->post("/admin/identities/{$identity->id}/manual-verify", [
            'reason' => 'Customer verified in-person at office desk',
        ]);
        $successResponse->assertSessionHas('success');

        $identity->refresh();
        $this->assertEquals('active', $identity->status);
        $this->assertEquals($this->superAdmin->id, $identity->verified_by_user_id);
        $this->assertEquals('Customer verified in-person at office desk', $identity->manual_verification_reason);

        // Assert immutable audit log created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => 'whatsapp.manual_verification_override',
            'reason' => 'Customer verified in-person at office desk',
        ]);
    }

    public function test_super_admin_can_reschedule_reminder_with_audit_reason(): void
    {
        $reminder = Reminder::create([
            'user_id' => $this->regularCustomer->id,
            'title' => 'Reschedule Test',
            'due_at' => Carbon::now('UTC')->addDay(),
            'status' => Reminder::STATUS_SCHEDULED,
            'recurrence' => Reminder::RECURRENCE_NONE,
        ]);

        $newDate = Carbon::now('UTC')->addDays(3)->format('Y-m-d\TH:i');

        $response = $this->actingAs($this->superAdmin)->post("/admin/reminders/{$reminder->id}/reschedule", [
            'due_at' => $newDate,
            'reason' => 'Customer requested date change via support email',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->superAdmin->id,
            'action' => 'reminder.admin_rescheduled',
            'reason' => 'Customer requested date change via support email',
        ]);
    }

    public function test_all_admin_module_screens_render_successfully(): void
    {
        $screens = [
            '/admin',
            '/admin/customers',
            '/admin/customers/create',
            '/admin/whatsapp',
            '/admin/reminders',
            '/admin/vault',
            '/admin/ai',
            '/admin/system',
            '/admin/audit-logs',
        ];

        foreach ($screens as $url) {
            $response = $this->actingAs($this->superAdmin)->get($url);
            $response->assertStatus(200);
        }
    }
}
