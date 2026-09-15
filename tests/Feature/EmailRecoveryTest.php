<?php

namespace Tests\Feature;

use App\Enums\EmailRecoveryStatus;
use App\Enums\UserRole;
use App\Models\EmailRecoveryRequest;
use App\Models\User;
use App\Notifications\EmailRecoveryStatusNotification;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

/**
 * Lost-email recovery — the guest request page and the admin review queue.
 * The API twin is covered by Api\V1\EmailRecoveryApiTest.
 */
class EmailRecoveryTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('local');
    }

    private function citizen(): User
    {
        return $this->makeCitizen([
            'phone' => '0555123456',
            'birth_date' => '1990-01-01',
            'email' => 'lost@mazayada.test',
        ]);
    }

    private function admin(): User
    {
        $admin = $this->makeCitizen(['role' => UserRole::SUPER_ADMIN]);
        $admin->syncRoles(UserRole::SUPER_ADMIN->value);

        return $admin;
    }

    /** @return array<string, mixed> */
    private function payload(User $user, array $overrides = []): array
    {
        return array_merge([
            'nin' => $user->nin,
            'birth_date' => '1990-01-01',
            'phone' => '0555123456',
            'new_email' => 'found@mazayada.test',
            'selfie_with_id' => UploadedFile::fake()->image('selfie.jpg', 600, 600)->size(300),
        ], $overrides);
    }

    private function openRequest(User $user): EmailRecoveryRequest
    {
        $this->post('/recover-email', $this->payload($user))->assertSessionHasNoErrors();

        return EmailRecoveryRequest::where('user_id', $user->id)->firstOrFail();
    }

    // ===== Citizen side =====

    public function test_login_page_links_to_the_recovery_page_which_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee(route('email-recovery.create'), false);
        $this->get('/recover-email')->assertOk()->assertSee(__('email_recovery.form.title'));
    }

    public function test_matching_identity_creates_a_pending_request_with_a_private_selfie(): void
    {
        $user = $this->citizen();

        $this->post('/recover-email', $this->payload($user))
            ->assertRedirect(route('email-recovery.create'))
            ->assertSessionHas('email_recovery_submitted', true);

        $request = EmailRecoveryRequest::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(EmailRecoveryStatus::PENDING, $request->status);
        $this->assertSame('lost@mazayada.test', $request->old_email);
        $this->assertSame('found@mazayada.test', $request->new_email);
        Storage::disk('local')->assertExists($request->selfie_with_id_path);

        // The account itself is untouched until an admin approves.
        $this->assertSame('lost@mazayada.test', $user->fresh()->email);
    }

    public function test_mismatched_birth_date_and_phone_are_rejected(): void
    {
        $user = $this->citizen();

        $this->post('/recover-email', $this->payload($user, [
            'birth_date' => '1991-02-02',
            'phone' => '0666000000',
        ]))->assertSessionHasErrors(['birth_date', 'phone']);

        $this->assertSame(0, EmailRecoveryRequest::count());
    }

    public function test_unknown_nin_taken_email_and_missing_selfie_fail_validation(): void
    {
        $user = $this->citizen();
        $other = $this->makeCitizen();

        $this->post('/recover-email', $this->payload($user, [
            'nin' => '000000000000000001',
            'new_email' => $other->email,
            'selfie_with_id' => null,
        ]))->assertSessionHasErrors(['nin', 'new_email', 'selfie_with_id']);
    }

    public function test_only_one_open_request_per_account(): void
    {
        $user = $this->citizen();
        $this->openRequest($user);

        $this->post('/recover-email', $this->payload($user, ['new_email' => 'second@mazayada.test']))
            ->assertSessionHasErrors('nin');

        $this->assertSame(1, EmailRecoveryRequest::count());
    }

    public function test_staff_accounts_cannot_use_the_flow(): void
    {
        $admin = $this->admin();

        $this->post('/recover-email', $this->payload($admin, [
            'birth_date' => '1990-01-01',
            'phone' => $admin->phone,
        ]))->assertSessionHasErrors('nin');
    }

    public function test_submissions_are_throttled_per_nin(): void
    {
        $user = $this->citizen();

        for ($i = 0; $i < 3; $i++) {
            $this->post('/recover-email', $this->payload($user, ['phone' => '0666000000']));
        }

        $this->post('/recover-email', $this->payload($user))
            ->assertSessionHasErrors(['nin' => __('email_recovery.errors.too_many', ['minutes' => 60])]);
    }

    public function test_status_lookup_shows_the_latest_request(): void
    {
        $user = $this->citizen();
        $this->openRequest($user);

        $this->post('/recover-email/status', ['status_nin' => $user->nin])
            ->assertOk()
            ->assertSee(EmailRecoveryStatus::PENDING->label())
            ->assertSee('f***@mazayada.test');

        $this->post('/recover-email/status', ['status_nin' => '000000000000000009'])
            ->assertOk()
            ->assertSee(__('email_recovery.lookup.none'));
    }

    // ===== Admin side =====

    public function test_admin_queue_and_review_page_render_with_the_selfie(): void
    {
        $user = $this->citizen();
        $request = $this->openRequest($user);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.email-recovery.index'))
            ->assertOk()
            ->assertSee('found@mazayada.test');

        $this->actingAs($admin)->get(route('admin.email-recovery.show', $request))
            ->assertOk()
            ->assertSee(route('admin.email-recovery.selfie', $request), false);

        $this->actingAs($admin)->get(route('admin.email-recovery.selfie', $request))->assertOk();
    }

    public function test_citizens_cannot_reach_the_admin_queue(): void
    {
        $user = $this->citizen();
        $request = $this->openRequest($user);

        $this->actingAs($user)->get(route('admin.email-recovery.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.email-recovery.approve', $request))->assertForbidden();
    }

    public function test_approval_replaces_the_email_revokes_access_and_notifies(): void
    {
        Notification::fake();

        $user = $this->citizen();
        $request = $this->openRequest($user);
        $user->createToken('phone');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.email-recovery.start-review', $request))->assertSessionHasNoErrors();
        $this->assertSame(EmailRecoveryStatus::UNDER_REVIEW, $request->fresh()->status);

        $this->actingAs($admin)->post(route('admin.email-recovery.approve', $request))
            ->assertRedirect(route('admin.email-recovery.index'));

        $request->refresh();
        $this->assertSame(EmailRecoveryStatus::APPROVED, $request->status);
        $this->assertSame($admin->id, $request->reviewed_by);
        $this->assertNotNull($request->reviewed_at);
        $this->assertSame('found@mazayada.test', $user->fresh()->email);
        $this->assertSame(0, $user->tokens()->count());

        Notification::assertSentTo($user, EmailRecoveryStatusNotification::class,
            fn (EmailRecoveryStatusNotification $n, array $channels) => $n->type === 'approved' && in_array('mail', $channels, true));
    }

    public function test_rejection_keeps_the_email_and_mails_the_requested_address(): void
    {
        Notification::fake();

        $user = $this->citizen();
        $request = $this->openRequest($user);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.email-recovery.reject', $request), ['reason' => 'صورة غير واضحة'])
            ->assertRedirect(route('admin.email-recovery.index'));

        $request->refresh();
        $this->assertSame(EmailRecoveryStatus::REJECTED, $request->status);
        $this->assertSame('صورة غير واضحة', $request->rejection_reason);
        $this->assertSame('lost@mazayada.test', $user->fresh()->email);

        Notification::assertSentTo($user, EmailRecoveryStatusNotification::class,
            fn (EmailRecoveryStatusNotification $n, array $channels) => ! in_array('mail', $channels, true));
        Notification::assertSentOnDemand(EmailRecoveryStatusNotification::class,
            fn ($n, array $channels, object $notifiable) => $notifiable->routes['mail'] === 'found@mazayada.test');

        // A rejected request no longer blocks a fresh one (the page is guest-only).
        auth()->logout();
        $this->post('/recover-email', $this->payload($user))->assertSessionHasNoErrors();
        $this->assertSame(2, EmailRecoveryRequest::count());
    }

    public function test_decided_requests_cannot_be_decided_again(): void
    {
        $user = $this->citizen();
        $request = $this->openRequest($user);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.email-recovery.reject', $request), ['reason' => 'x']);

        $this->actingAs($admin)->post(route('admin.email-recovery.approve', $request))
            ->assertSessionHasErrors('status');

        $this->assertSame('lost@mazayada.test', $user->fresh()->email);
    }

    public function test_approval_fails_when_the_address_was_claimed_meanwhile(): void
    {
        $user = $this->citizen();
        $request = $this->openRequest($user);
        $this->makeCitizen(['email' => 'found@mazayada.test']);

        $this->actingAs($this->admin())->post(route('admin.email-recovery.approve', $request))
            ->assertSessionHasErrors('status');

        $this->assertSame(EmailRecoveryStatus::PENDING, $request->fresh()->status);
        $this->assertSame('lost@mazayada.test', $user->fresh()->email);
    }
}
