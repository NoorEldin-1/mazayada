<?php

namespace Tests\Feature;

use App\Enums\AppealStatus;
use App\Enums\AuctionStatus;
use App\Enums\UserRole;
use App\Models\Appeal;
use App\Models\Auction;
use App\Models\Bid;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

/**
 * The three-party appeals (الطعون) workflow:
 * citizen → platform admin → organising entity → platform admin → citizen.
 */
class AppealWorkflowTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    // ===== Eligibility =====

    public function test_eligibility_requires_closed_window_participation_and_a_bid(): void
    {
        $user = $this->makeCitizen();
        $auction = $this->makeAuction(['status' => AuctionStatus::CLOSED, 'closed_at' => now()]);

        // Participant but no bid yet.
        $this->makeParticipant($auction, $user);
        $this->assertFalse($auction->canBeAppealedBy($user));

        // With a valid bid -> eligible.
        $this->placeBid($auction, $user);
        $this->assertTrue($auction->fresh()->canBeAppealedBy($user));

        // A non-participant with a bid is still ineligible.
        $stranger = $this->makeCitizen();
        $this->placeBid($auction, $stranger);
        $this->assertFalse($auction->fresh()->canBeAppealedBy($stranger));
    }

    public function test_eligibility_is_false_while_live_and_after_the_window(): void
    {
        $user = $this->makeCitizen();

        $live = $this->makeAuction(['status' => AuctionStatus::ACTIVE]);
        $this->makeParticipant($live, $user);
        $this->placeBid($live, $user);
        $this->assertFalse($live->fresh()->canBeAppealedBy($user));

        $expired = $this->makeAuction([
            'status' => AuctionStatus::CLOSED,
            'closed_at' => now()->subDays($live->appealWindowDays() + 1),
        ]);
        $this->makeParticipant($expired, $user);
        $this->placeBid($expired, $user);
        $this->assertFalse($expired->fresh()->canBeAppealedBy($user));
    }

    // ===== Happy path =====

    public function test_full_flow_citizen_to_approved(): void
    {
        $this->superAdmin();                       // a recipient for the intake notice
        $citizen = $this->makeCitizen();
        $auction = $this->eligibleAuction($citizen);

        // 1) Citizen files from the auction page.
        $this->actingAs($citizen)
            ->post(route('auctions.appeals.store', $auction), [
                'subject' => 'اعتراض', 'reason' => 'سبب الطعن',
            ])->assertRedirect();

        $appeal = Appeal::firstWhere('auction_id', $auction->id);
        $this->assertSame(AppealStatus::PENDING, $appeal->status);
        $this->assertSame(AppealStatus::PENDING, $appeal->status->publicStatus());

        // 2) Platform admin forwards to the organising entity.
        $this->actingAs($this->superAdmin())
            ->post(route('admin.appeals.forward', $appeal))->assertRedirect();
        $this->assertSame(AppealStatus::FORWARDED_TO_ENTITY, $appeal->fresh()->status);

        // 3) Entity approves.
        $this->actingAs($this->entityViewer($auction->entity_id))
            ->post(route('admin.appeals.decide', $appeal), [
                'decision' => 'APPROVED', 'entity_response' => 'موافقة الجهة',
            ])->assertRedirect();
        $this->assertSame(AppealStatus::ENTITY_APPROVED, $appeal->fresh()->status);

        // 4) Platform admin confirms — terminal, citizen sees APPROVED.
        $this->actingAs($this->superAdmin())
            ->post(route('admin.appeals.confirm', $appeal), [
                'decision' => 'APPROVED', 'admin_response' => 'تأكيد القبول',
            ])->assertRedirect();

        $appeal->refresh();
        $this->assertSame(AppealStatus::APPROVED, $appeal->status);
        $this->assertSame(AppealStatus::APPROVED, $appeal->status->publicStatus());
        $this->assertNotNull($appeal->resolved_at);
    }

    public function test_admin_can_reject_at_intake_without_forwarding(): void
    {
        $citizen = $this->makeCitizen();
        $auction = $this->eligibleAuction($citizen);
        $appeal = $this->fileAppeal($citizen, $auction);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.appeals.reject', $appeal), ['admin_response' => 'مخالف'])
            ->assertRedirect();

        $this->assertSame(AppealStatus::REJECTED, $appeal->fresh()->status);
    }

    public function test_admin_can_override_the_entity_decision(): void
    {
        $citizen = $this->makeCitizen();
        $auction = $this->eligibleAuction($citizen);
        $appeal = $this->fileAppeal($citizen, $auction);

        $this->actingAs($this->superAdmin())->post(route('admin.appeals.forward', $appeal));
        $this->actingAs($this->entityViewer($auction->entity_id))
            ->post(route('admin.appeals.decide', $appeal), [
                'decision' => 'REJECTED', 'entity_response' => 'رفض الجهة',
            ]);

        // Admin overrides the entity's rejection and approves.
        $this->actingAs($this->superAdmin())
            ->post(route('admin.appeals.confirm', $appeal), [
                'decision' => 'APPROVED', 'admin_response' => 'تجاوز قرار الجهة',
            ])->assertRedirect();

        $appeal->refresh();
        $this->assertSame(AppealStatus::APPROVED, $appeal->status);
        $this->assertSame(AppealStatus::REJECTED, $appeal->entity_decision);
    }

    // ===== Guards =====

    public function test_only_one_appeal_per_auction_per_user(): void
    {
        $citizen = $this->makeCitizen();
        $auction = $this->eligibleAuction($citizen);

        $this->actingAs($citizen)->post(route('auctions.appeals.store', $auction), [
            'subject' => 'أول', 'reason' => 'سبب',
        ])->assertRedirect();

        $this->actingAs($citizen)->post(route('auctions.appeals.store', $auction), [
            'subject' => 'ثاني', 'reason' => 'سبب آخر',
        ])->assertRedirect();

        $this->assertSame(1, Appeal::where('auction_id', $auction->id)->count());
    }

    public function test_ineligible_user_cannot_file(): void
    {
        $citizen = $this->makeCitizen();
        // Closed auction but the user never bid.
        $auction = $this->makeAuction(['status' => AuctionStatus::CLOSED, 'closed_at' => now()]);

        $this->actingAs($citizen)
            ->post(route('auctions.appeals.store', $auction), [
                'subject' => 'اعتراض', 'reason' => 'سبب',
            ])->assertForbidden();

        $this->assertSame(0, Appeal::count());
    }

    public function test_admin_cannot_confirm_before_the_entity_decides(): void
    {
        $citizen = $this->makeCitizen();
        $auction = $this->eligibleAuction($citizen);
        $appeal = $this->fileAppeal($citizen, $auction);
        $this->actingAs($this->superAdmin())->post(route('admin.appeals.forward', $appeal));

        // SUPER_ADMIN bypasses the policy (Gate::before), so the illegal-transition
        // guard is the AppealService: it rejects gracefully and leaves the state.
        $this->actingAs($this->superAdmin())
            ->post(route('admin.appeals.confirm', $appeal), [
                'decision' => 'APPROVED', 'admin_response' => 'سابق لأوانه',
            ])->assertSessionHas('error');

        $this->assertSame(AppealStatus::FORWARDED_TO_ENTITY, $appeal->fresh()->status);
    }

    // ===== helpers =====

    private function eligibleAuction(User $user): Auction
    {
        $auction = $this->makeAuction(['status' => AuctionStatus::CLOSED, 'closed_at' => now()]);
        $this->makeParticipant($auction, $user);
        $this->placeBid($auction, $user);

        return $auction;
    }

    private function fileAppeal(User $user, Auction $auction): Appeal
    {
        $this->actingAs($user)->post(route('auctions.appeals.store', $auction), [
            'subject' => 'اعتراض', 'reason' => 'سبب الطعن',
        ]);

        return Appeal::firstWhere('auction_id', $auction->id);
    }

    private function placeBid(Auction $auction, User $user): Bid
    {
        return Bid::create([
            'auction_id' => $auction->id,
            'user_id' => $user->id,
            'amount' => 1_100_000,
            'bid_time' => now(),
            'is_valid' => true,
        ]);
    }

    private function superAdmin(): User
    {
        if ($existing = User::where('role', UserRole::SUPER_ADMIN->value)->first()) {
            return $existing;
        }

        $user = $this->makeCitizen(['role' => UserRole::SUPER_ADMIN, 'entity_id' => null]);
        $user->syncRoles([UserRole::SUPER_ADMIN->value]);

        return $user;
    }

    private function entityViewer(string $entityId): User
    {
        $user = $this->makeCitizen([
            'role' => UserRole::ENTITY_VIEWER,
            'entity_id' => $entityId,
        ]);
        $user->syncRoles([UserRole::ENTITY_VIEWER->value]);

        return $user;
    }
}
