<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Models\Document;
use App\Models\Payment;
use App\Policies\DocumentPolicy;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

/**
 * The condition book is a PAID download: gated in DocumentPolicy by
 * Auction::hasBookAccess (free book or a confirmed purchase).
 */
class ConditionBookAccessTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function conditionBook($auction): Document
    {
        $doc = new Document([
            'auction_id' => $auction->id,
            'type' => DocumentType::CONDITION_BOOK,
            'title' => 'كراسة الشروط',
            'file_path' => 'CONDITION_BOOK/'.Str::uuid().'.pdf',
            'disk' => 'documents',
            'is_public' => false,
        ]);
        $doc->id = (string) Str::uuid();
        $doc->save();

        return $doc;
    }

    public function test_priced_book_is_blocked_until_purchased(): void
    {
        $auction = $this->makeAuction(['book_price' => 300_000]);
        $doc = $this->conditionBook($auction);
        $buyer = $this->makeCitizen();
        $stranger = $this->makeCitizen();
        $policy = new DocumentPolicy();

        // No purchase → no access.
        $this->assertFalse($policy->download($stranger, $doc));
        $this->assertFalse($policy->download($buyer, $doc));

        Payment::create([
            'user_id' => $buyer->id,
            'auction_id' => $auction->id,
            'payment_type' => PaymentType::BOOK_PURCHASE,
            'amount' => 300_000,
            'status' => PaymentStatus::CONFIRMED,
            'gateway' => 'mock',
        ]);

        $this->assertTrue($policy->download($buyer->fresh(), $doc->fresh()));
        // A different user still cannot read it.
        $this->assertFalse($policy->download($stranger->fresh(), $doc->fresh()));
    }

    public function test_free_book_is_readable_by_any_authenticated_user(): void
    {
        $auction = $this->makeAuction(['book_price' => 0]);
        $doc = $this->conditionBook($auction);

        $this->assertTrue((new DocumentPolicy())->download($this->makeCitizen(), $doc));
    }

    public function test_pending_purchase_does_not_grant_access(): void
    {
        $auction = $this->makeAuction(['book_price' => 300_000]);
        $doc = $this->conditionBook($auction);
        $user = $this->makeCitizen();

        Payment::create([
            'user_id' => $user->id,
            'auction_id' => $auction->id,
            'payment_type' => PaymentType::BOOK_PURCHASE,
            'amount' => 300_000,
            'status' => PaymentStatus::PENDING,
            'gateway' => 'mock',
        ]);

        $this->assertFalse((new DocumentPolicy())->download($user, $doc));
    }
}
