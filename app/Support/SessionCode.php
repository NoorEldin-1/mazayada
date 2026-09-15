<?php

namespace App\Support;

use App\Models\Auction;

/**
 * Generates the stable session reference printed on receipts and shown to
 * citizens (client edit 5): SES-{year}-{sequence}, sequence restarting yearly.
 * The column is unique, so a lost race surfaces as a constraint error rather
 * than a silent duplicate.
 */
final class SessionCode
{
    public static function next(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $prefix = "SES-{$year}-";

        // Global scopes off: the entity scope would hide other entities' codes.
        $last = Auction::withoutGlobalScopes()
            ->where('session_code', 'like', $prefix.'%')
            ->orderByRaw('LENGTH(session_code) DESC')
            ->orderByDesc('session_code')
            ->value('session_code');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
