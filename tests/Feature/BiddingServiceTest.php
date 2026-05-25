<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\AssetCondition;
use App\Enums\AuctionStatus;
use App\Enums\AuctionType;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Events\BidPlaced;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\Category;
use App\Models\Entity;
use App\Models\User;
use App\Models\Wilaya;
use App\Services\BiddingService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class BiddingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_can_place_a_valid_bid(): void
    {
        Event::fake([BidPlaced::class]);

        [$auction, $user] = $this->setupActiveAuctionAndParticipant();

        $bid = app(BiddingService::class)->placeBid($auction, $user, 1_500_000); // 15,000 DZD in centimes

        $this->assertNotNull($bid->id);
        $this->assertSame(1_500_000, (int) $bid->amount);
        Event::assertDispatched(BidPlaced::class);
    }

    public function test_cannot_bid_below_or_equal_to_current_highest(): void
    {
        [$auction, $user] = $this->setupActiveAuctionAndParticipant();

        $this->expectException(RuntimeException::class);
        app(BiddingService::class)->placeBid($auction, $user, $auction->opening_price);
    }

    public function test_cannot_bid_without_paid_deposit(): void
    {
        [$auction, $user] = $this->setupActiveAuctionAndParticipant(depositPaid: false);

        $this->expectException(RuntimeException::class);
        app(BiddingService::class)->placeBid($auction, $user, 2_000_000);
    }

    public function test_cannot_bid_on_closed_auction(): void
    {
        [$auction, $user] = $this->setupActiveAuctionAndParticipant();
        $auction->update(['status' => AuctionStatus::CLOSED]);

        $this->expectException(RuntimeException::class);
        app(BiddingService::class)->placeBid($auction, $user, 2_000_000);
    }

    public function test_cannot_bid_after_end_time(): void
    {
        [$auction, $user] = $this->setupActiveAuctionAndParticipant();
        $auction->update(['end_time' => now()->subMinutes(5)]);

        $this->expectException(RuntimeException::class);
        app(BiddingService::class)->placeBid($auction, $user, 2_000_000);
    }

    public function test_bid_in_last_seconds_triggers_auto_extension(): void
    {
        [$auction, $user] = $this->setupActiveAuctionAndParticipant();
        // End in 10 seconds, trigger window is 30 seconds.
        $auction->update([
            'end_time' => now()->addSeconds(10),
            'extension_trigger_seconds' => 30,
            'extension_duration_minutes' => 5,
        ]);

        app(BiddingService::class)->placeBid($auction, $user, 2_000_000);

        $auction->refresh();
        $this->assertSame(AuctionStatus::EXTENDED, $auction->status);
        $this->assertTrue($auction->end_time->greaterThan(now()->addMinutes(4)));
    }

    public function test_blacklisted_user_cannot_bid(): void
    {
        [$auction, $user] = $this->setupActiveAuctionAndParticipant();
        $user->update(['is_blacklisted' => true, 'blacklist_reason' => 'fraud']);

        $this->expectException(RuntimeException::class);
        app(BiddingService::class)->placeBid($auction->fresh(), $user->fresh(), 2_000_000);
    }

    /**
     * @return array{0: Auction, 1: User}
     */
    private function setupActiveAuctionAndParticipant(bool $depositPaid = true): array
    {
        $wilaya = Wilaya::create([
            'id' => 40, 'code' => '40', 'name_ar' => 'خنشلة', 'name_fr' => 'Khenchela',
        ]);

        $entity = Entity::create([
            'name' => 'APC Khenchela',
            'name_ar' => 'بلدية خنشلة',
            'name_fr' => 'APC Khenchela',
            'type' => 'MUNICIPALITY',
            'wilaya_id' => $wilaya->id,
        ]);

        $category = Category::create([
            'name_ar' => 'مركبات',
            'name_fr' => 'Vehicules',
            'name_en' => 'Vehicles',
        ]);

        $auction = Auction::create([
            'entity_id' => $entity->id,
            'category_id' => $category->id,
            'title_ar' => 'سيارة للبيع',
            'description_ar' => 'وصف',
            'condition' => AssetCondition::GOOD,
            'unit_count' => 1,
            'opening_price' => 1_000_000,
            'deposit_amount' => 100_000,
            'entry_fee' => 50_000,
            'book_price' => 300_000,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'extension_trigger_seconds' => 30,
            'extension_duration_minutes' => 5,
            'status' => AuctionStatus::ACTIVE,
            'auction_type' => AuctionType::SALE,
        ]);

        $user = User::create([
            'nin' => '109823041175663878',
            'first_name_ar' => 'مزايد',
            'last_name_ar' => 'تجربة',
            'phone' => '0555000111',
            'email' => 'bidder@example.test',
            'birth_date' => '1990-01-01',
            'password' => 'StrongP@ss123',
            'role' => UserRole::CITIZEN,
            'kyc_status' => KycStatus::COMPLETE,
            'kyc_completed_at' => now(),
            'account_status' => AccountStatus::ACTIVE,
            'phone_verified' => true,
            'email_verified' => true,
        ]);
        $user->assignRole(UserRole::CITIZEN->value);

        AuctionParticipant::create([
            'auction_id' => $auction->id,
            'user_id' => $user->id,
            'deposit_paid' => $depositPaid,
            'entry_fee_paid' => true,
            'registered_at' => now(),
        ]);

        return [$auction, $user];
    }
}
