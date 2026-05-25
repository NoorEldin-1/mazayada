<?php

namespace Tests\Unit;

use App\Services\BidderAliasService;
use Tests\TestCase;

class BidderAliasServiceTest extends TestCase
{
    public function test_alias_is_deterministic_for_same_user_and_auction(): void
    {
        $service = new BidderAliasService;
        $userId = 'user-uuid-1';
        $auctionId = 'auction-uuid-1';

        $first = $service->aliasFor($userId, $auctionId);
        $second = $service->aliasFor($userId, $auctionId);

        $this->assertSame($first, $second);
    }

    public function test_alias_differs_across_auctions_for_the_same_user(): void
    {
        $service = new BidderAliasService;
        $userId = 'user-uuid-1';

        $aliasA = $service->aliasFor($userId, 'auction-uuid-A');
        $aliasB = $service->aliasFor($userId, 'auction-uuid-B');

        $this->assertNotSame($aliasA, $aliasB);
    }

    public function test_alias_does_not_contain_raw_identifiers(): void
    {
        $service = new BidderAliasService;
        $userId = 'super-secret-user-uuid';
        $auctionId = 'super-secret-auction-uuid';

        $alias = $service->aliasFor($userId, $auctionId);

        $this->assertStringNotContainsString($userId, $alias);
        $this->assertStringNotContainsString($auctionId, $alias);
        $this->assertMatchesRegularExpression('/^[A-Z][a-z]+_[A-Z][a-z]+_\d+$/', $alias);
    }
}
