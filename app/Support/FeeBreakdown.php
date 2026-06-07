<?php

namespace App\Support;

/**
 * Immutable result of a Decree 97-33 fee computation (spec §2.2). All amounts
 * are integer centimes. Built only by App\Services\FeeCalculator and consumed by
 * the checkout (final-payment amount) and the award/receipt PDFs.
 *
 * The buyer pays: hammerPrice + appraisalFee + hammerFee + proportionalBuyer +
 * workSessionFee + tva. The proportionalSeller is borne by the seller and shown
 * for information only (never added to buyerTotal).
 */
final readonly class FeeBreakdown
{
    public function __construct(
        public int $hammerPrice,
        public int $appraisalFee,
        public int $hammerFee,
        public int $proportionalSeller,
        public int $proportionalBuyer,
        public int $workSessionFee,
        public int $tvaBase,
        public int $tva,
        public int $buyerTotal,
        public ?int $customsImmediateDue = null,
    ) {}

    /**
     * Display lines for the receipt / award fee table. Each entry is an
     * i18n key + a centimes amount; the view formats with dzd().
     *
     * @return array<int, array{key: string, amount: int}>
     */
    public function lines(): array
    {
        $lines = [
            ['key' => 'fees.line.hammer_price', 'amount' => $this->hammerPrice],
            ['key' => 'fees.line.appraisal_fee', 'amount' => $this->appraisalFee],
        ];

        if ($this->hammerFee > 0) {
            $lines[] = ['key' => 'fees.line.hammer_fee', 'amount' => $this->hammerFee];
        }

        $lines[] = ['key' => 'fees.line.proportional_buyer', 'amount' => $this->proportionalBuyer];
        $lines[] = ['key' => 'fees.line.work_session', 'amount' => $this->workSessionFee];
        $lines[] = ['key' => 'fees.line.tva', 'amount' => $this->tva];
        $lines[] = ['key' => 'fees.line.buyer_total', 'amount' => $this->buyerTotal];

        return $lines;
    }

    /** @return array<string, int|null> */
    public function toArray(): array
    {
        return [
            'hammer_price' => $this->hammerPrice,
            'appraisal_fee' => $this->appraisalFee,
            'hammer_fee' => $this->hammerFee,
            'proportional_seller' => $this->proportionalSeller,
            'proportional_buyer' => $this->proportionalBuyer,
            'work_session_fee' => $this->workSessionFee,
            'tva_base' => $this->tvaBase,
            'tva' => $this->tva,
            'buyer_total' => $this->buyerTotal,
            'customs_immediate_due' => $this->customsImmediateDue,
        ];
    }
}
