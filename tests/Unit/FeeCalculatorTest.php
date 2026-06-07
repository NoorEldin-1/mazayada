<?php

namespace Tests\Unit;

use App\Enums\AssetClass;
use App\Models\Auction;
use App\Services\FeeCalculator;
use Tests\TestCase;

/**
 * Exact-centime checks for the Decree 97-33 progressive fee schedule (spec §2.2).
 * No DB — FeeCalculator falls back to config('mazayada.fees.*').
 */
class FeeCalculatorTest extends TestCase
{
    private function auction(AssetClass $class): Auction
    {
        $a = new Auction();
        $a->asset_class = $class;

        return $a;
    }

    public function test_movable_breakdown_at_100k_dzd(): void
    {
        // 100,000 DZD = 10,000,000 centimes.
        $fees = app(FeeCalculator::class)->forAward($this->auction(AssetClass::MOVABLE), 10_000_000);

        $this->assertSame(130_000, $fees->appraisalFee);   // 2% of 30k + 1% of 70k DZD
        $this->assertSame(480_000, $fees->hammerFee);      // 6% of 60k + 3% of 40k DZD
        $this->assertSame(300_000, $fees->proportionalBuyer);
        $this->assertSame(500_000, $fees->proportionalSeller);
        $this->assertSame(100_000, $fees->workSessionFee);
        $this->assertSame(1_010_000, $fees->tvaBase);
        $this->assertSame(191_900, $fees->tva);            // 19% of tvaBase
        $this->assertSame(11_201_900, $fees->buyerTotal);
        $this->assertNull($fees->customsImmediateDue);
    }

    public function test_real_estate_has_no_hammer_fee(): void
    {
        $fees = app(FeeCalculator::class)->forAward($this->auction(AssetClass::REAL_ESTATE), 10_000_000);

        $this->assertSame(0, $fees->hammerFee);
        $this->assertSame(130_000, $fees->appraisalFee);
        $this->assertSame(530_000, $fees->tvaBase);
        $this->assertSame(100_700, $fees->tva);
        $this->assertSame(10_630_700, $fees->buyerTotal);
    }

    public function test_customs_adds_20_percent_immediate_due(): void
    {
        $fees = app(FeeCalculator::class)->forAward($this->auction(AssetClass::CUSTOMS), 10_000_000);

        // Same as movable buyer total, plus the 20% immediate portion.
        $this->assertSame(11_201_900, $fees->buyerTotal);
        $this->assertSame(2_240_380, $fees->customsImmediateDue); // ceil(11,201,900 * 0.20)
    }
}
