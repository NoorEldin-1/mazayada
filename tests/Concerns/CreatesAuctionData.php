<?php

namespace Tests\Concerns;

use App\Enums\AccountStatus;
use App\Enums\AssetCondition;
use App\Enums\AuctionStatus;
use App\Enums\AuctionType;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\Category;
use App\Models\Entity;
use App\Models\User;
use App\Models\Wilaya;
use Illuminate\Support\Str;

/**
 * Shared builders for the lifecycle feature tests. Mirrors the inline
 * construction used by BiddingServiceTest, deduplicated.
 */
trait CreatesAuctionData
{
    protected ?Wilaya $refWilaya = null;
    protected ?Entity $refEntity = null;
    protected ?Category $refCategory = null;

    protected function refs(): void
    {
        if ($this->refWilaya) {
            return;
        }

        $this->refWilaya = Wilaya::create([
            'id' => 40, 'code' => '40', 'name_ar' => 'خنشلة', 'name_fr' => 'Khenchela',
        ]);
        $this->refEntity = Entity::create([
            'name' => 'APC Khenchela', 'name_ar' => 'بلدية خنشلة',
            'name_fr' => 'APC Khenchela', 'type' => 'MUNICIPALITY', 'wilaya_id' => $this->refWilaya->id,
        ]);
        $this->refCategory = Category::create([
            'name_ar' => 'مركبات', 'name_fr' => 'Vehicules', 'name_en' => 'Vehicles',
        ]);
    }

    protected function makeAuction(array $overrides = []): Auction
    {
        $this->refs();

        return Auction::create(array_merge([
            'entity_id' => $this->refEntity->id,
            'category_id' => $this->refCategory->id,
            'title_ar' => 'سيارة للبيع',
            'description_ar' => 'وصف',
            'condition' => AssetCondition::GOOD,
            'unit_count' => 1,
            'opening_price' => 1_000_000,
            'deposit_amount' => 100_000,
            'entry_fee' => 50_000,
            'book_price' => 0,
            'start_time' => now()->subHour(),
            'end_time' => now()->addHour(),
            'status' => AuctionStatus::ACTIVE,
            'auction_type' => AuctionType::SALE,
        ], $overrides));
    }

    protected function makeCitizen(array $overrides = []): User
    {
        $user = User::create(array_merge([
            'nin' => $this->randomNin(),
            'first_name_ar' => 'مزايد',
            'last_name_ar' => 'تجربة',
            'phone' => '05'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'email' => Str::random(10).'@example.test',
            'birth_date' => '1990-01-01',
            'password' => 'StrongP@ss123',
            'role' => UserRole::CITIZEN,
            'kyc_status' => KycStatus::COMPLETE,
            'kyc_completed_at' => now(),
            'account_status' => AccountStatus::ACTIVE,
            'phone_verified' => true,
            'email_verified' => true,
        ], $overrides));
        $user->assignRole(UserRole::CITIZEN->value);

        return $user;
    }

    protected function makeParticipant(Auction $auction, User $user, array $overrides = []): AuctionParticipant
    {
        return AuctionParticipant::create(array_merge([
            'auction_id' => $auction->id,
            'user_id' => $user->id,
            'deposit_paid' => true,
            'entry_fee_paid' => true,
            'registered_at' => now(),
        ], $overrides));
    }

    private function randomNin(): string
    {
        return str_pad((string) random_int(0, 999999999999999999), 18, '0', STR_PAD_LEFT);
    }
}
