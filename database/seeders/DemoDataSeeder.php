<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\AuctionStatus;
use App\Enums\AuctionType;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\Auction;
use App\Models\AuctionParticipant;
use App\Models\Bid;
use App\Models\Entity;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Demo citizens
        $citizens = [
            ['109823041175663829', 'سامي', 'بن عيسى', 'Sami', 'Benaissa', '0555412098', 'sami.b@mail.dz', '1992-03-14', UserRole::PREMIUM_CITIZEN, KycStatus::COMPLETE],
            ['109823041176553914', 'ياسمين', 'بوزيد', 'Yasmine', 'Bouzid', '0661220845', 'y.bouzid@mail.dz', '1995-07-22', UserRole::CITIZEN, KycStatus::PENDING],
            ['109823041177331022', 'خالد', 'العمراني', 'Khaled', 'Lamrani', '0770998542', 'k.lamrani@mail.dz', '1988-11-03', UserRole::CITIZEN, KycStatus::PENDING],
            ['109823041178492011', 'فاطمة الزهراء', 'قاسم', 'Fatima Z.', 'Kacem', '0555663712', 'fz.kacem@mail.dz', '1990-05-10', UserRole::CITIZEN, KycStatus::COMPLETE],
            ['109823041179884429', 'محمد', 'العيد', 'Mohammed', 'Elaid', '0661145209', 'm.elaid@mail.dz', '1987-09-30', UserRole::CITIZEN, KycStatus::SUSPENDED],
            ['109823041180227714', 'أمين', 'بلهادي', 'Amine', 'Belhadi', '0770552117', 'a.belhadi@mail.dz', '1993-12-05', UserRole::CITIZEN, KycStatus::COMPLETE],
        ];

        $userModels = [];
        foreach ($citizens as [$nin, $fAr, $lAr, $fFr, $lFr, $phone, $email, $dob, $role, $kyc]) {
            $userModels[] = User::create([
                'nin' => $nin,
                'first_name_ar' => $fAr,
                'last_name_ar' => $lAr,
                'first_name_fr' => $fFr,
                'last_name_fr' => $lFr,
                'phone' => $phone,
                'email' => $email,
                'birth_date' => $dob,
                'password' => 'Password@2026!',
                'role' => $role,
                'kyc_status' => $kyc,
                'kyc_completed_at' => $kyc === KycStatus::COMPLETE ? now() : null,
                'account_status' => $kyc === KycStatus::SUSPENDED ? AccountStatus::SUSPENDED : AccountStatus::ACTIVE,
                'phone_verified' => true,
                'email_verified' => true,
            ]);
        }

        // Demo auctions
        $entities = Entity::all();
        $auctionsData = [
            ['شاحنة هينو 700 — موديل 2019', 'Camion Hino 700 — 2019', 1, AuctionStatus::ACTIVE, AuctionType::SALE, 280_000_00, 50_000_00, 10_000_00, 16, 0],
            ['محل تجاري — شارع ديدوش مراد', 'Local commercial — Rue Didouche Mourad', 2, AuctionStatus::ACTIVE, AuctionType::LEASE, 1_800_000_00, 200_000_00, 50_000_00, 16, 1],
            ['حاسوب مكتبي ديل — 35 وحدة', 'Ordinateur Dell — 35 unités', 4, AuctionStatus::EXTENDED, AuctionType::SALE, 42_000_00, 10_000_00, 5_000_00, 31, 4],
            ['أرض فلاحية — 12 هكتار', 'Terrain agricole — 12 hectares', 2, AuctionStatus::PUBLISHED, AuctionType::LEASE, 9_500_000_00, 1_000_000_00, 100_000_00, 19, 2],
            ['معدات كسارة حصى', 'Équipement de concassage', 3, AuctionStatus::ACTIVE, AuctionType::SALE, 850_000_00, 100_000_00, 20_000_00, 35, 0],
            ['مكاتب خشبية — 60 قطعة', 'Bureaux en bois — 60 pièces', 7, AuctionStatus::DRAFT, AuctionType::SALE, 18_000_00, 5_000_00, 2_000_00, 25, 4],
            ['سيارة رينو سيمبول 2021', 'Renault Symbol 2021', 1, AuctionStatus::CLOSED, AuctionType::SALE, 120_000_00, 30_000_00, 10_000_00, 13, 3],
            ['قمح صلب — 220 قنطار', 'Blé dur — 220 quintaux', 6, AuctionStatus::CLOSED, AuctionType::SALE, 140_000_00, 20_000_00, 5_000_00, 14, 2],
        ];

        foreach ($auctionsData as $i => [$titleAr, $titleFr, $catId, $status, $type, $opening, $deposit, $entry, $wilayaId, $entityIdx]) {
            $entity = $entities[$entityIdx % $entities->count()];
            $startTime = match ($status) {
                AuctionStatus::DRAFT => now()->addDays(7),
                AuctionStatus::PUBLISHED => now()->addDays(2),
                AuctionStatus::CLOSED => now()->subDays(3),
                default => now()->subHours(rand(1, 48)),
            };
            $endTime = match ($status) {
                AuctionStatus::DRAFT => now()->addDays(10),
                AuctionStatus::PUBLISHED => now()->addDays(5),
                AuctionStatus::CLOSED => now()->subDays(1),
                default => now()->addHours(rand(6, 72)),
            };

            $auction = Auction::create([
                'entity_id' => $entity->id,
                'category_id' => $catId,
                'title_ar' => $titleAr,
                'title_fr' => $titleFr,
                'condition' => ['NEW', 'GOOD', 'FAIR', 'GOOD', 'FAIR', 'GOOD', 'GOOD', 'NEW'][$i],
                'unit_count' => [1, 1, 35, 1, 1, 60, 1, 220][$i],
                'opening_price' => $opening,
                'deposit_amount' => $deposit,
                'entry_fee' => $entry,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => $status,
                'auction_type' => $type,
                'lease_duration_years' => $type === AuctionType::LEASE ? 3 : null,
                'wilaya_id' => $wilayaId,
            ]);

            // Add bids for active/extended/closed auctions
            if (in_array($status, [AuctionStatus::ACTIVE, AuctionStatus::EXTENDED, AuctionStatus::CLOSED])) {
                $verifiedUsers = collect($userModels)->filter(fn($u) => $u->kyc_status === KycStatus::COMPLETE);
                $currentPrice = $opening;

                foreach ($verifiedUsers->take(3) as $user) {
                    AuctionParticipant::create([
                        'auction_id' => $auction->id,
                        'user_id' => $user->id,
                        'deposit_paid' => true,
                        'entry_fee_paid' => true,
                        'registered_at' => now()->subDays(rand(1, 5)),
                    ]);

                    for ($b = 0; $b < rand(2, 5); $b++) {
                        $currentPrice += rand(1, 10) * 100_00;
                        Bid::create([
                            'auction_id' => $auction->id,
                            'user_id' => $user->id,
                            'amount' => $currentPrice,
                            'bid_time' => now()->subMinutes(rand(5, 500)),
                            'ip_address' => '41.96.' . rand(1, 255) . '.' . rand(1, 255),
                            'is_valid' => true,
                        ]);
                    }
                }

                if ($status === AuctionStatus::CLOSED) {
                    $winningBid = $auction->bids()->orderByDesc('amount')->first();
                    if ($winningBid) {
                        $auction->update([
                            'winner_user_id' => $winningBid->user_id,
                            'final_price' => $winningBid->amount,
                        ]);
                    }
                }
            }
        }

        // Sample notifications
        $admin = User::where('email', 'admin@mazayada.dz')->first();
        if ($admin) {
            foreach ([
                ['مزايدة جديدة على شاحنة هينو', 'تم تقديم عرض جديد بقيمة 3,500,000 دج'],
                ['طلب تحقق هوية جديد', 'المستخدم خالد العمراني أرسل وثائق KYC للمراجعة'],
                ['طعن جديد', 'ياسمين بوزيد قدمت طعنا حول رسوم الدخول'],
            ] as [$title, $body]) {
                UserNotification::create([
                    'user_id' => $admin->id,
                    'title' => $title,
                    'body' => $body,
                    'channel' => 'PUSH',
                    'created_at' => now()->subMinutes(rand(5, 300)),
                ]);
            }
        }
    }
}
