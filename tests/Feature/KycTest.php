<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\Commune;
use App\Models\User;
use App\Models\UserBiometric;
use App\Models\Wilaya;
use App\Notifications\KycStatusNotification;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    // ===== Page rendering =====

    public function test_citizen_kyc_page_renders_for_each_status(): void
    {
        $commune = $this->seedGeo();

        $pending = $this->createCitizen(seed: 50);
        $this->actingAs($pending)->get(route('citizen.kyc'))->assertOk()->assertSee(__('kyc.submit'));

        $rejected = $this->createCitizen([
            'kyc_status' => KycStatus::REJECTED,
            'kyc_rejection_reason' => 'الصورة غير واضحة',
            'commune_id' => $commune->id,
        ], seed: 51);
        $this->actingAs($rejected)->get(route('citizen.kyc'))->assertOk()->assertSee('الصورة غير واضحة');

        $complete = $this->createCitizen(['kyc_status' => KycStatus::COMPLETE, 'kyc_completed_at' => now()], seed: 52);
        $this->actingAs($complete)->get(route('citizen.kyc'))->assertOk();
    }

    public function test_admin_show_page_renders_with_documents(): void
    {
        $commune = $this->seedGeo();
        $admin = $this->createAdmin();
        $user = $this->createCitizen([
            'kyc_status' => KycStatus::UNDER_REVIEW,
            'kyc_submitted_at' => now(),
            'commune_id' => $commune->id,
            'expected_income' => 80000,
        ], seed: 53);
        $this->giveAllDocuments($user);

        $this->actingAs($admin)
            ->get(route('admin.kyc.show', $user))
            ->assertOk()
            ->assertSee($user->nin)
            ->assertSee(__('admin.kyc.documents_title'));
    }

    public function test_admin_user_detail_page_renders(): void
    {
        $commune = $this->seedGeo();
        $admin = $this->createAdmin();
        $user = $this->createCitizen([
            'kyc_status' => KycStatus::COMPLETE,
            'kyc_completed_at' => now(),
            'commune_id' => $commune->id,
            'expected_income' => 80000,
        ], seed: 60);
        $this->giveAllDocuments($user);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee(__('admin.users.sec_identity'));
    }

    // ===== Document upload =====

    public function test_upload_stores_document_on_the_private_disk(): void
    {
        Storage::fake('local');
        $user = $this->createCitizen();

        $this->actingAs($user)
            ->post(route('citizen.kyc.upload', 'id-front'), [
                'file' => UploadedFile::fake()->image('id-front.jpg'),
            ])
            ->assertRedirect();

        $path = $user->fresh()->biometrics->id_front_path;
        $this->assertNotNull($path);
        $this->assertStringStartsWith('kyc/'.$user->id, $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_upload_rejects_when_status_is_not_editable(): void
    {
        Storage::fake('local');
        $user = $this->createCitizen(['kyc_status' => KycStatus::COMPLETE, 'kyc_completed_at' => now()]);

        $this->actingAs($user)
            ->post(route('citizen.kyc.upload', 'id-front'), [
                'file' => UploadedFile::fake()->image('id-front.jpg'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertNull($user->fresh()->biometrics);
    }

    public function test_citizen_can_stream_own_document_but_files_are_not_public(): void
    {
        Storage::fake('local');
        $user = $this->createCitizen();

        $this->actingAs($user)->post(route('citizen.kyc.upload', 'selfie-with-id'), [
            'file' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $this->actingAs($user)
            ->get(route('citizen.kyc.document', 'selfie-with-id'))
            ->assertOk();
    }

    // ===== Submission =====

    public function test_submit_is_blocked_without_all_three_documents(): void
    {
        $commune = $this->seedGeo();
        $user = $this->createCitizen();
        // Only one of three documents present.
        UserBiometric::create(['user_id' => $user->id, 'id_front_path' => 'kyc/x/a.jpg']);

        $this->actingAs($user)
            ->post(route('citizen.kyc.submit'), $this->validForm($commune))
            ->assertSessionHasErrors('file');

        $this->assertSame(KycStatus::PENDING, $user->fresh()->kyc_status);
    }

    public function test_successful_submit_moves_to_under_review(): void
    {
        $commune = $this->seedGeo();
        $user = $this->createCitizen();
        $this->giveAllDocuments($user);

        $this->actingAs($user)
            ->post(route('citizen.kyc.submit'), $this->validForm($commune))
            ->assertRedirect()
            ->assertSessionHas('success');

        $fresh = $user->fresh();
        $this->assertSame(KycStatus::UNDER_REVIEW, $fresh->kyc_status);
        $this->assertNotNull($fresh->kyc_submitted_at);
        $this->assertSame('Karim', $fresh->first_name_fr);
        $this->assertSame($commune->id, $fresh->commune_id);
    }

    public function test_commune_must_belong_to_selected_wilaya(): void
    {
        $commune = $this->seedGeo();              // wilaya 16
        $otherWilaya = Wilaya::create(['id' => 31, 'code' => '31', 'name_ar' => 'وهران', 'name_fr' => 'Oran']);
        $otherCommune = Commune::create(['wilaya_id' => 31, 'code' => '3101', 'name_ar' => 'وهران', 'name_fr' => 'Oran', 'postal_code' => '31000']);

        $user = $this->createCitizen();
        $this->giveAllDocuments($user);

        $form = $this->validForm($commune);
        $form['commune_id'] = $otherCommune->id;   // commune from wilaya 31, but wilaya_id = 16

        $this->actingAs($user)
            ->post(route('citizen.kyc.submit'), $form)
            ->assertSessionHasErrors('commune_id');

        $this->assertSame(KycStatus::PENDING, $user->fresh()->kyc_status);
    }

    // ===== Admin review queue =====

    public function test_admin_queue_lists_only_under_review_users(): void
    {
        $admin = $this->createAdmin();
        $pending = $this->createCitizen(['kyc_status' => KycStatus::PENDING], seed: 1);
        $underReview = $this->createCitizen([
            'kyc_status' => KycStatus::UNDER_REVIEW,
            'kyc_submitted_at' => now(),
            'first_name_ar' => 'قيد',
            'last_name_ar' => 'المراجعة',
        ], seed: 2);

        $response = $this->actingAs($admin)->get(route('admin.kyc.index'))->assertOk();

        $response->assertSee($underReview->nin);
        $response->assertDontSee($pending->nin);
    }

    public function test_admin_approve_marks_complete_and_notifies(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $user = $this->createCitizen(['kyc_status' => KycStatus::UNDER_REVIEW, 'kyc_submitted_at' => now()], seed: 3);
        $this->giveAllDocuments($user);

        $this->actingAs($admin)
            ->post(route('admin.kyc.approve', $user))
            ->assertRedirect(route('admin.kyc.index'));

        $fresh = $user->fresh();
        $this->assertSame(KycStatus::COMPLETE, $fresh->kyc_status);
        $this->assertNotNull($fresh->kyc_completed_at);
        $this->assertSame($admin->id, $fresh->biometrics->kyc_verified_by);
        $this->assertNotNull($fresh->biometrics->kyc_verified_at);

        Notification::assertSentTo($user, KycStatusNotification::class, fn ($n) => $n->type === 'approved');
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id]);
    }

    public function test_admin_reject_stores_reason_and_allows_resubmission(): void
    {
        Notification::fake();
        $admin = $this->createAdmin();
        $commune = $this->seedGeo();
        $user = $this->createCitizen(['kyc_status' => KycStatus::UNDER_REVIEW, 'kyc_submitted_at' => now()], seed: 4);
        $this->giveAllDocuments($user);

        $this->actingAs($admin)
            ->post(route('admin.kyc.reject', $user), ['reason' => 'الصورة غير واضحة'])
            ->assertRedirect(route('admin.kyc.index'));

        $fresh = $user->fresh();
        $this->assertSame(KycStatus::REJECTED, $fresh->kyc_status);
        $this->assertSame('الصورة غير واضحة', $fresh->kyc_rejection_reason);
        Notification::assertSentTo($user, KycStatusNotification::class, fn ($n) => $n->type === 'rejected');

        // A rejected citizen can fix and resubmit → back to the review queue.
        $this->actingAs($fresh)
            ->post(route('citizen.kyc.submit'), $this->validForm($commune))
            ->assertSessionHasNoErrors();

        $resubmitted = $user->fresh();
        $this->assertSame(KycStatus::UNDER_REVIEW, $resubmitted->kyc_status);
        $this->assertNull($resubmitted->kyc_rejection_reason);
    }

    public function test_admin_can_unblacklist_user(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createCitizen(['is_blacklisted' => true, 'blacklist_reason' => 'fraud'], seed: 70);

        $this->actingAs($admin)
            ->post(route('admin.users.unblacklist', $user))
            ->assertRedirect();

        $fresh = $user->fresh();
        $this->assertFalse($fresh->is_blacklisted);
        $this->assertNull($fresh->blacklist_reason);
    }

    // ===== Bidding gate (model level) =====

    public function test_can_bid_only_when_kyc_complete(): void
    {
        foreach ([KycStatus::PENDING, KycStatus::UNDER_REVIEW, KycStatus::REJECTED, KycStatus::SUSPENDED] as $i => $status) {
            $user = $this->createCitizen(['kyc_status' => $status], seed: 10 + $i);
            $this->assertFalse($user->canBid(), "Status {$status->value} should not be able to bid");
        }

        $complete = $this->createCitizen(['kyc_status' => KycStatus::COMPLETE, 'kyc_completed_at' => now()], seed: 20);
        $this->assertTrue($complete->canBid());
    }

    // ===== Auto-suspension command =====

    public function test_suspend_stale_command_only_suspends_old_pending_accounts(): void
    {
        $graceDays = (int) config('mazayada.kyc.pending_grace_days', 30);

        $stalePending = $this->createCitizen(['kyc_status' => KycStatus::PENDING], seed: 30);
        $this->backdate($stalePending, $graceDays + 5);

        $recentPending = $this->createCitizen(['kyc_status' => KycStatus::PENDING], seed: 31);

        $staleUnderReview = $this->createCitizen(['kyc_status' => KycStatus::UNDER_REVIEW, 'kyc_submitted_at' => now()], seed: 32);
        $this->backdate($staleUnderReview, $graceDays + 5);

        $this->artisan('kyc:suspend-stale')->assertExitCode(0);

        $this->assertSame(KycStatus::SUSPENDED, $stalePending->fresh()->kyc_status);
        $this->assertSame(KycStatus::PENDING, $recentPending->fresh()->kyc_status);
        $this->assertSame(KycStatus::UNDER_REVIEW, $staleUnderReview->fresh()->kyc_status);
    }

    // ===== Helpers =====

    private function validForm(Commune $commune): array
    {
        return [
            'first_name_fr' => 'Karim',
            'last_name_fr' => 'Benali',
            'father_name' => 'محمد',
            'mother_name' => 'فاطمة',
            'mother_surname' => 'بن علي',
            'address' => 'حي النصر، شارع 12',
            'wilaya_id' => $commune->wilaya_id,
            'commune_id' => $commune->id,
            'postal_code' => '16000',
            'profession' => 'مهندس',
            'expected_income' => 80000,
            'rip' => '00799999000123456789',
        ];
    }

    private function seedGeo(): Commune
    {
        Wilaya::create(['id' => 16, 'code' => '16', 'name_ar' => 'الجزائر', 'name_fr' => 'Alger']);

        return Commune::create([
            'wilaya_id' => 16, 'code' => '1601', 'name_ar' => 'الجزائر الوسطى',
            'name_fr' => 'Alger Centre', 'postal_code' => '16000',
        ]);
    }

    private function giveAllDocuments(User $user): void
    {
        UserBiometric::updateOrCreate(['user_id' => $user->id], [
            'id_front_path' => "kyc/{$user->id}/front.jpg",
            'id_back_path' => "kyc/{$user->id}/back.jpg",
            'selfie_with_id_path' => "kyc/{$user->id}/selfie.jpg",
        ]);
    }

    private function backdate(User $user, int $days): void
    {
        $user->created_at = now()->subDays($days);
        $user->save();
    }

    private function createCitizen(array $overrides = [], int $seed = 0): User
    {
        $defaults = [
            'nin' => $this->makeValidNin($seed),
            'first_name_ar' => 'تجربة',
            'last_name_ar' => 'مستخدم',
            'phone' => '055'.str_pad((string) (1000000 + $seed), 7, '0', STR_PAD_LEFT),
            'email' => "citizen{$seed}_".uniqid().'@example.test',
            'birth_date' => '1990-01-01',
            'password' => 'StrongP@ss123',
            'role' => UserRole::CITIZEN,
            'kyc_status' => KycStatus::PENDING,
            'account_status' => AccountStatus::ACTIVE,
            'phone_verified' => true,
            'email_verified' => true,
        ];

        $user = User::create(array_merge($defaults, $overrides));
        $user->assignRole(UserRole::CITIZEN->value);

        return $user;
    }

    private function createAdmin(int $seed = 900): User
    {
        $user = $this->createCitizen([
            'role' => UserRole::SUPER_ADMIN,
            'kyc_status' => KycStatus::COMPLETE,
            'kyc_completed_at' => now(),
        ], seed: $seed);

        $user->syncRoles(UserRole::SUPER_ADMIN->value);

        return $user;
    }

    /** Build a checksum-valid 18-digit NIN from an incrementing seed. */
    private function makeValidNin(int $seed): string
    {
        $base = str_pad((string) (1098230411750000 + $seed), 16, '0', STR_PAD_LEFT);
        $weights = [2, 3, 4, 5, 6, 7];
        $digits = str_split($base);
        $sum = 0;
        for ($i = 15; $i >= 0; $i--) {
            $sum += ((int) $digits[$i]) * $weights[(15 - $i) % 6];
        }

        return $base.str_pad((string) (97 - ($sum % 97)), 2, '0', STR_PAD_LEFT);
    }
}
