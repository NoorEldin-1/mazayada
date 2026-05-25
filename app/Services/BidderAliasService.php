<?php

namespace App\Services;

/**
 * Deterministic bidder aliases (Section 6.5 of the technical specification).
 *
 * Same user ⇒ same alias inside the same auction, but never reveal the real name.
 * The HMAC is keyed by ALIAS_SECRET so aliases are not predictable to outsiders.
 */
class BidderAliasService
{
    private const ADJECTIVES = ['Swift', 'Bold', 'Sharp', 'Keen', 'Brave'];

    private const NOUNS = ['Falcon', 'Lion', 'Eagle', 'Hawk', 'Star'];

    public function aliasFor(string $userId, string $auctionId): string
    {
        $secret = (string) env('ALIAS_SECRET', config('app.key'));
        $hash = hash_hmac('sha256', $userId.$auctionId, $secret);

        $adjective = self::ADJECTIVES[hexdec(substr($hash, 0, 2)) % count(self::ADJECTIVES)];
        $noun = self::NOUNS[hexdec(substr($hash, 2, 2)) % count(self::NOUNS)];
        $number = hexdec(substr($hash, 4, 2));

        return sprintf('%s_%s_%d', $adjective, $noun, $number);
    }
}
