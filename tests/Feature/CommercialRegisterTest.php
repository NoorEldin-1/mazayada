<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\CommercialRegisterStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\CommercialRegister;
use App\Models\User;
use App\Notifications\CommercialRegisterStatusNotification;
use App\Services\PaymentService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

class CommercialRegisterTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('documents');
    }

    // ===== Page rendering =====

    public function test_citizen_page_renders_for_each_status(): void
    {
        $none = $this->makeCitizen();
        $this->actingAs($none)->get(route('citizen.commercial-register'))
            ->assertOk()->assertSee(__('commercial-register.banner_none_title'));

        $rejected = $this->makeCitizen();
        $this->makeRegister($rejected, [
            'status' => CommercialRegisterStatus::REJECTED,
            'rejection_reason' => 'الوثيقة غير مقروءة',
        ]);
        $this->actingAs($rejected)->get(route('citizen.commercial-register'))
            ->assertOk()->assertSee('الوثيقة غير مقروءة');
    }

    // ===== Submission =====

    public function test_successful_submit_creates_pending_record_and_stores_files(): void
    {
        Storage::fake('local');
        $user = $this->makeCitizen();

        $this->actingAs($user)
            ->post(route('citizen.commercial-register.store'), $this->validForm())
            ->assertRedirect()
            ->assertSessionHas('success');

        $register = $user->fresh()->commercialRegister;
        $this->assertNotNull($register);
        $this->assertSame(CommercialRegisterStatus::PENDING, $register->status);
        $this->assertSame('شركة الأمل', $register->company_name);
        $this->assertNotNull($register->submitted_at);

        $this->assertStringStartsWith('commercial-registers/'.$user->id, $register->register_document_path);
        Storage::disk('local')->assertExists($register->register_document_path);
        Storage::disk('local')->assertExists($register->tax_card_document_path);
    }

    public function test_submit_is_locked_while_pending_or_approved(): void
    {
        $user = $this->makeCitizen();
        $this->makeRegister($user, ['status' => CommercialRegisterStatus::APPROVED]);

        $this->actingAs($user)
            ->post(route('citizen.commercial-register.store'), $this->validForm())
            ->assertForbidden();
    }

    // ===== Admin review queue =====

    public function test_admin_queue_lists_only_pending(): void
    {
        $admin = $this->makeAdmin();

        $pendingUser = $this->makeCitizen();
        $pending = $this->makeRegister($pendingUser, ['status' => CommercialRegisterStatus::PENDING, 'company_name' => 'شركة قيد المراجعة']);

        $approvedUser = $this->makeCitizen();
        $this->makeRegister($approvedUser, ['status' => CommercialRegisterStatus::APPROVED, 'company_name' => 'شركة معتمدة']);

        $this->actingAs($admin)->get(route('admin.commercial-registers.index'))
            ->assertOk()
            ->assertSee('شركة قيد المراجعة')
            ->assertDontSee('شركة معتمدة');
    }

    public function test_admin_approve_marks_approved_and_notifies(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $user = $this->makeCitizen();
        $register = $this->makeRegister($user, ['status' => CommercialRegisterStatus::PENDING]);

        $this->actingAs($admin)
            ->post(route('admin.commercial-registers.approve', $register))
            ->assertRedirect(route('admin.commercial-registers.index'));

        $fresh = $register->fresh();
        $this->assertSame(CommercialRegisterStatus::APPROVED, $fresh->status);
        $this->assertNotNull($fresh->reviewed_at);
        $this->assertSame($admin->id, $fresh->reviewed_by);

        Notification::assertSentTo($user, CommercialRegisterStatusNotification::class, fn ($n) => $n->type === 'approved');
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id]);
    }

    public function test_admin_reject_stores_reason_and_allows_resubmission(): void
    {
        Notification::fake();
        Storage::fake('local');
        $admin = $this->makeAdmin();
        $user = $this->makeCitizen();
        $register = $this->makeRegister($user, ['status' => CommercialRegisterStatus::PENDING]);

        $this->actingAs($admin)
            ->post(route('admin.commercial-registers.reject', $register), ['reason' => 'رقم السجل غير صحيح'])
            ->assertRedirect(route('admin.commercial-registers.index'));

        $fresh = $register->fresh();
        $this->assertSame(CommercialRegisterStatus::REJECTED, $fresh->status);
        $this->assertSame('رقم السجل غير صحيح', $fresh->rejection_reason);
        Notification::assertSentTo($user, CommercialRegisterStatusNotification::class, fn ($n) => $n->type === 'rejected');

        // A rejected user can fix and resubmit → back to PENDING, reason cleared.
        $this->actingAs($user->fresh())
            ->post(route('citizen.commercial-register.store'), $this->validForm())
            ->assertSessionHasNoErrors();

        $resubmitted = $register->fresh();
        $this->assertSame(CommercialRegisterStatus::PENDING, $resubmitted->status);
        $this->assertNull($resubmitted->rejection_reason);
    }

    // ===== hasCommerceRegister() semantics =====

    public function test_only_an_approved_register_grants_access(): void
    {
        $none = $this->makeCitizen();
        $this->assertFalse($none->hasCommerceRegister());

        $pendingUser = $this->makeCitizen();
        $this->makeRegister($pendingUser, ['status' => CommercialRegisterStatus::PENDING]);
        $this->assertFalse($pendingUser->fresh()->hasCommerceRegister());

        $approvedUser = $this->makeCitizen();
        $this->makeRegister($approvedUser, ['status' => CommercialRegisterStatus::APPROVED]);
        $this->assertTrue($approvedUser->fresh()->hasCommerceRegister());
    }

    // ===== Participation gate =====

    public function test_registration_blocked_without_valid_register(): void
    {
        $auction = $this->makeAuction(['requires_commerce_register' => true, 'book_price' => 0]);
        $user = $this->makeCitizen();

        $this->expectException(RuntimeException::class);
        app(PaymentService::class)->initiateRegistration($auction, $user);
    }

    public function test_registration_allowed_with_valid_register(): void
    {
        $auction = $this->makeAuction(['requires_commerce_register' => true, 'book_price' => 0, 'deposit_amount' => 100_000]);
        $user = $this->makeCitizen();
        $this->makeRegister($user, ['status' => CommercialRegisterStatus::APPROVED]);

        $result = app(PaymentService::class)->initiateRegistration($auction, $user->fresh());

        $this->assertArrayHasKey('ref', $result);
    }

    public function test_book_purchase_also_blocked_without_valid_register(): void
    {
        $auction = $this->makeAuction(['requires_commerce_register' => true, 'book_price' => 300_000]);
        $user = $this->makeCitizen();

        $this->expectException(RuntimeException::class);
        app(PaymentService::class)->initiateBookPurchase($auction, $user);
    }

    // ===== Helpers =====

    private function validForm(): array
    {
        return [
            'company_name' => 'شركة الأمل',
            'register_number' => '16/00-1234567 A 09',
            'tax_number' => '000116001234567',
            'activity_type' => 'تجارة بالجملة',
            'start_date' => now()->subYear()->format('Y-m-d'),
            'register_document' => UploadedFile::fake()->create('register.pdf', 200, 'application/pdf'),
            'tax_card_document' => UploadedFile::fake()->image('tax-card.jpg'),
        ];
    }

    private function makeRegister(User $user, array $overrides = []): CommercialRegister
    {
        return CommercialRegister::create(array_merge([
            'user_id' => $user->id,
            'company_name' => 'شركة تجربة',
            'register_number' => '16/00-0000001 A 09',
            'tax_number' => '000116000000001',
            'activity_type' => 'تجارة',
            'start_date' => now()->subYear(),
            'register_document_path' => 'commercial-registers/'.$user->id.'/register.pdf',
            'tax_card_document_path' => 'commercial-registers/'.$user->id.'/tax.jpg',
            'status' => CommercialRegisterStatus::PENDING,
            'submitted_at' => now(),
        ], $overrides));
    }

    private function makeAdmin(): User
    {
        $user = User::create([
            'nin' => str_pad((string) random_int(0, 999999999999999999), 18, '0', STR_PAD_LEFT),
            'first_name_ar' => 'مشرف',
            'last_name_ar' => 'عام',
            'phone' => '05'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'email' => Str::random(10).'@example.test',
            'birth_date' => '1985-01-01',
            'password' => 'StrongP@ss123',
            'role' => UserRole::SUPER_ADMIN,
            'kyc_status' => KycStatus::COMPLETE,
            'kyc_completed_at' => now(),
            'account_status' => AccountStatus::ACTIVE,
            'phone_verified' => true,
            'email_verified' => true,
        ]);
        $user->syncRoles(UserRole::SUPER_ADMIN->value);

        return $user;
    }
}
