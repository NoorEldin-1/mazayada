<?php

namespace App\Services;

use App\Enums\AssetClass;
use App\Models\Auction;
use App\Support\FeeBreakdown;

/**
 * Computes the judicial-officer fees (Decree 97-33) + TVA for an award
 * (spec §2.2). Pure and deterministic — same inputs always give the same
 * centimes — so it is trivially unit-testable.
 *
 * Tiers are PROGRESSIVE (marginal): each rate applies only to the portion of
 * the value that falls within its tier, exactly like income-tax brackets.
 *
 * Rate source: scalar rates resolve through setting() (Super-Admin editable),
 * the progressive tier tables come from config/mazayada.php.
 */
class FeeCalculator
{
    public function forAward(Auction $auction, int $hammerPriceCentimes): FeeBreakdown
    {
        $hammerPrice = max(0, $hammerPriceCentimes);
        $isRealEstate = ($auction->asset_class ?? AssetClass::MOVABLE) === AssetClass::REAL_ESTATE;

        $appraisalFee = $this->progressive($hammerPrice, (array) config('mazayada.fees.appraisal_tiers', []));

        // The movables hammer-fee tiers do NOT apply to real estate (Decree 97-33);
        // real estate is covered by the proportional rights only.
        $hammerFee = $isRealEstate
            ? 0
            : $this->progressive($hammerPrice, (array) config('mazayada.fees.hammer_tiers', []));

        $sellerRate = (float) setting('fees.proportional_seller', config('mazayada.fees.proportional_seller', 0.05));
        $buyerRate = (float) setting('fees.proportional_buyer', config('mazayada.fees.proportional_buyer', 0.03));
        $tvaRate = (float) setting('fees.tva_rate', config('mazayada.fees.tva_rate', 0.19));
        $workSession = (int) setting('fees.work_session_flat_centimes', config('mazayada.fees.work_session_flat_centimes', 100_000));

        $proportionalSeller = (int) round($hammerPrice * $sellerRate);
        $proportionalBuyer = (int) round($hammerPrice * $buyerRate);

        // TVA is charged on the buyer-borne judicial fees (not on the sale price):
        // "these fees are in addition to TVA" (spec §2.2).
        $tvaBase = $appraisalFee + $hammerFee + $proportionalBuyer + $workSession;
        $tva = (int) round($tvaBase * $tvaRate);

        $buyerTotal = $hammerPrice + $tvaBase + $tva;

        $customsImmediateDue = null;
        if (($auction->asset_class ?? null) === AssetClass::CUSTOMS) {
            $customsRate = (float) setting('fees.customs_min_immediate_rate', config('mazayada.fees.customs_min_immediate_rate', 0.20));
            $customsImmediateDue = (int) ceil($buyerTotal * $customsRate);
        }

        return new FeeBreakdown(
            hammerPrice: $hammerPrice,
            appraisalFee: $appraisalFee,
            hammerFee: $hammerFee,
            proportionalSeller: $proportionalSeller,
            proportionalBuyer: $proportionalBuyer,
            workSessionFee: $workSession,
            tvaBase: $tvaBase,
            tva: $tva,
            buyerTotal: $buyerTotal,
            customsImmediateDue: $customsImmediateDue,
        );
    }

    /**
     * Progressive (marginal) fee: each tier's rate applies only to the portion
     * of $amount within that tier. A null `upTo` means "and above".
     *
     * @param  array<int, array{upTo: int|null, rate: float}>  $tiers
     */
    private function progressive(int $amount, array $tiers): int
    {
        $fee = 0.0;
        $lower = 0;

        foreach ($tiers as $tier) {
            $upper = $tier['upTo'] ?? PHP_INT_MAX;

            if ($amount <= $lower) {
                break;
            }

            $portion = min($amount, $upper) - $lower;
            if ($portion > 0) {
                $fee += $portion * (float) $tier['rate'];
            }

            $lower = $upper;
        }

        return (int) round($fee);
    }
}
