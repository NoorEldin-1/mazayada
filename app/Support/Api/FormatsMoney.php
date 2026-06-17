<?php

namespace App\Support\Api;

/**
 * Consistent money representation for API resources. All amounts cross the API
 * boundary as { amount: <whole dinars>, formatted: "<localized DZD string>" },
 * while the database/services keep integer centimes.
 */
trait FormatsMoney
{
    /**
     * @return array{amount:int, formatted:string}
     */
    protected function money(int|null $centimes): array
    {
        return [
            'amount' => dinars($centimes),
            'formatted' => dzd($centimes),
        ];
    }
}
