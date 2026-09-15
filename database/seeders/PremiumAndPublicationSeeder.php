<?php

namespace Database\Seeders;

use App\Enums\PublicationArea;
use App\Enums\SubscriptionPeriod;
use App\Models\PublicationPackage;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

/**
 * Starter Premium plans (edit 24) and publication packages (edit 16). Idempotent
 * — matched on `code`, and an existing row is never overwritten, so prices an
 * admin has edited survive a re-seed. Amounts in centimes.
 */
class PremiumAndPublicationSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['MONTHLY', 'الباقة الشهرية', 'Formule mensuelle', 'Monthly plan', SubscriptionPeriod::MONTHLY, 150_000, false, 1],
            ['YEARLY', 'الباقة السنوية', 'Formule annuelle', 'Yearly plan', SubscriptionPeriod::YEARLY, 1_200_000, true, 2],
        ];

        foreach ($plans as [$code, $ar, $fr, $en, $period, $price, $recommended, $sort]) {
            SubscriptionPlan::firstOrCreate(['code' => $code], [
                'name_ar' => $ar, 'name_fr' => $fr, 'name_en' => $en,
                'period' => $period, 'price' => $price, 'is_recommended' => $recommended, 'sort_order' => $sort,
                'features' => [
                    'ar' => ['تنبيه فوري بالمزايدات الجديدة قبل غير المشتركين', 'إشعارات البريد الإلكتروني حسب اهتماماتك', 'اختيار أنواع المزايدات المفضلة'],
                    'fr' => ['Alerte immédiate des nouvelles enchères, avant les non-abonnés', 'Alertes par e-mail selon vos intérêts', 'Choix des types d\'enchères préférés'],
                    'en' => ['Instant new-auction alerts, ahead of non-members', 'Email alerts matching your interests', 'Choose your preferred auction types'],
                ],
            ]);
        }

        $packages = [
            ['STANDARD', 'نشر في القائمة العامة', 'Liste générale', 'General listing', PublicationArea::LISTING, 500_000, 300_000, 1],
            ['SECTOR_TOP', 'أعلى قسم القطاع', 'En tête du secteur', 'Top of sector', PublicationArea::CATEGORY_TOP, 1_000_000, 500_000, 2],
            ['HOMEPAGE', 'الصفحة الرئيسية', 'Page d\'accueil', 'Home page', PublicationArea::HOMEPAGE, 2_000_000, 1_000_000, 3],
        ];

        foreach ($packages as [$code, $ar, $fr, $en, $area, $price, $priority, $sort]) {
            PublicationPackage::firstOrCreate(['code' => $code], [
                'name_ar' => $ar, 'name_fr' => $fr, 'name_en' => $en,
                'display_area' => $area, 'price' => $price, 'priority_price' => $priority,
                'duration_days' => 30, 'sort_order' => $sort,
            ]);
        }
    }
}
