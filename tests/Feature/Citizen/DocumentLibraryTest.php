<?php

namespace Tests\Feature\Citizen;

use App\Enums\DocumentType;
use App\Models\Auction;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentLibraryService;
use App\Support\DocumentFilters;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

class DocumentLibraryTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    private DocumentLibraryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        $this->service = app(DocumentLibraryService::class);
    }

    private function makeDocument(DocumentType $type, ?Auction $auction, ?User $user, array $overrides = []): Document
    {
        $doc = new Document(array_merge([
            'auction_id' => $auction?->id,
            'user_id' => $user?->id,
            'type' => $type,
            'title' => $type->value.'-'.Str::random(6),
            'file_path' => 'x/'.Str::uuid().'.pdf',
            'disk' => 'documents',
            'file_size' => 2048,
            'mime' => 'application/pdf',
            'signature' => hash('sha256', Str::random()),
            'is_public' => false,
        ], $overrides));
        $doc->id = (string) Str::uuid();
        $doc->save();

        return $doc;
    }

    /** @return array{0: User, 1: Auction} A citizen who participates in a fresh (free-book) auction. */
    private function citizenWithAuction(): array
    {
        $auction = $this->makeAuction(['title_ar' => 'AUCTION-MINE']);
        $citizen = $this->makeCitizen();
        $this->makeParticipant($auction, $citizen);

        return [$citizen, $auction];
    }

    public function test_library_scopes_to_the_users_engaged_auctions(): void
    {
        [$citizen, $auction] = $this->citizenWithAuction();

        $book = $this->makeDocument(DocumentType::CONDITION_BOOK, $auction, null, ['title' => 'BOOK-MINE']);
        $award = $this->makeDocument(DocumentType::AWARD, $auction, $citizen, ['title' => 'AWARD-MINE']);

        // A stranger's auction + documents the citizen never engaged with.
        $stranger = $this->makeCitizen();
        $otherAuction = $this->makeAuction(['title_ar' => 'AUCTION-THEIRS']);
        $this->makeDocument(DocumentType::CONDITION_BOOK, $otherAuction, null, ['title' => 'BOOK-THEIRS']);
        $this->makeDocument(DocumentType::AWARD, $otherAuction, $stranger, ['title' => 'AWARD-THEIRS']);

        // Admin-only report on the citizen's own auction is still hidden.
        $this->makeDocument(DocumentType::AUCTION_REPORT, $auction, null, ['title' => 'REPORT-HIDDEN']);

        $ids = $this->service->query($citizen, DocumentFilters::fromRequest(Request::create('/')))
            ->pluck('id');

        $this->assertContains($book->id, $ids);
        $this->assertContains($award->id, $ids);
        $this->assertCount(2, $ids);
    }

    public function test_page_renders_own_documents_and_hides_others(): void
    {
        [$citizen, $auction] = $this->citizenWithAuction();
        $this->makeDocument(DocumentType::CONDITION_BOOK, $auction, null, ['title' => 'BOOK-MINE']);

        $stranger = $this->makeCitizen();
        $otherAuction = $this->makeAuction();
        $this->makeDocument(DocumentType::AWARD, $otherAuction, $stranger, ['title' => 'AWARD-THEIRS']);

        $this->actingAs($citizen)
            ->get(route('citizen.documents'))
            ->assertOk()
            ->assertSee('BOOK-MINE')
            ->assertDontSee('AWARD-THEIRS');
    }

    public function test_type_filter_narrows_results(): void
    {
        [$citizen, $auction] = $this->citizenWithAuction();
        $this->makeDocument(DocumentType::CONDITION_BOOK, $auction, null, ['title' => 'BOOK-MINE']);
        $this->makeDocument(DocumentType::AWARD, $auction, $citizen, ['title' => 'AWARD-MINE']);

        $request = Request::create('/', 'GET', ['type' => [DocumentType::CONDITION_BOOK->value]]);
        $titles = $this->service->query($citizen, DocumentFilters::fromRequest($request))->pluck('title');

        $this->assertTrue($titles->contains('BOOK-MINE'));
        $this->assertFalse($titles->contains('AWARD-MINE'));
    }

    public function test_search_matches_auction_title(): void
    {
        [$citizen, $auction] = $this->citizenWithAuction(); // title AUCTION-MINE
        $this->makeDocument(DocumentType::AWARD, $auction, $citizen, ['title' => 'AWARD-MINE']);

        $hit = Request::create('/', 'GET', ['search' => 'AUCTION-MINE']);
        $this->assertCount(1, $this->service->query($citizen, DocumentFilters::fromRequest($hit))->get());

        $miss = Request::create('/', 'GET', ['search' => 'NOTHING-MATCHES']);
        $this->assertCount(0, $this->service->query($citizen, DocumentFilters::fromRequest($miss))->get());
    }

    public function test_stats_count_per_type(): void
    {
        [$citizen, $auction] = $this->citizenWithAuction();
        $this->makeDocument(DocumentType::CONDITION_BOOK, $auction, null);
        $this->makeDocument(DocumentType::AWARD, $auction, $citizen);
        $this->makeDocument(DocumentType::PAYMENT_RECEIPT, $auction, $citizen);
        $this->makeDocument(DocumentType::DELIVERY_REPORT, $auction, $citizen);

        // Under strict attribute access (the app's local runtime) the grouped
        // aggregate must NOT eager-load `auction` — that would read the unselected
        // `auction_id` and throw. This guards that regression.
        \Illuminate\Database\Eloquent\Model::preventAccessingMissingAttributes();
        try {
            $stats = $this->service->stats($citizen);
        } finally {
            \Illuminate\Database\Eloquent\Model::preventAccessingMissingAttributes(false);
        }

        $this->assertSame(4, $stats['total']);
        $this->assertSame(1, $stats['books']);
        $this->assertSame(1, $stats['awards']);
        $this->assertSame(2, $stats['receipts']); // receipt + delivery report
        $this->assertGreaterThan(0, $stats['total_bytes']);
    }
}
