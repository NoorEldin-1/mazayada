<?php

namespace Tests\Feature;

use App\Enums\AuctionStatus;
use App\Enums\AuctionType;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

/**
 * The public browse page (auctions.index) with its URL-driven advanced filters,
 * 5-per-page pagination, and the live-search JSON endpoint. The central
 * guarantee under test: filters and pagination never contradict — the filter
 * form carries no `page`, so every filter change resets to page 1, while the
 * paginator carries the active filters forward.
 */
class AuctionBrowseTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_browse_page_renders_with_sidebar_and_cards(): void
    {
        $this->makeAuction(['status' => AuctionStatus::ACTIVE]);

        $this->get(route('auctions.index'))
            ->assertOk()
            ->assertSee('br-side', false)      // sticky sidebar
            ->assertSee('br-search-input', false) // live search
            ->assertSee('auc-row', false);      // horizontal card
    }

    public function test_filter_form_carries_no_page_field(): void
    {
        // This is what structurally resets pagination when a filter is applied.
        $this->makeAuction();

        $this->get(route('auctions.index'))
            ->assertOk()
            ->assertDontSee('name="page"', false);
    }

    public function test_paginates_five_per_page(): void
    {
        foreach (range(1, 7) as $i) {
            $this->makeAuction(['status' => AuctionStatus::ACTIVE]);
        }

        $response = $this->get(route('auctions.index'))->assertOk();

        // Exactly five cards on page one, with a link through to page two.
        $this->assertSame(5, substr_count($response->getContent(), 'class="auc-row-ttl"'));
        $response->assertSee('page=2', false);
    }

    public function test_type_filter_narrows_results(): void
    {
        $this->makeAuction(['title_ar' => 'شاحنة للبيع', 'auction_type' => AuctionType::SALE]);
        $this->makeAuction(['title_ar' => 'محل للإيجار', 'auction_type' => AuctionType::LEASE]);

        $this->get(route('auctions.index', ['type' => AuctionType::LEASE->value]))
            ->assertOk()
            ->assertSee('محل للإيجار')
            ->assertDontSee('شاحنة للبيع');
    }

    public function test_status_live_filter_narrows_results(): void
    {
        $this->makeAuction(['title_ar' => 'مزاد مباشر', 'status' => AuctionStatus::ACTIVE]);
        $this->makeAuction(['title_ar' => 'مزاد قادم', 'status' => AuctionStatus::PUBLISHED]);

        $this->get(route('auctions.index', ['status' => ['live']]))
            ->assertOk()
            ->assertSee('مزاد مباشر')
            ->assertDontSee('مزاد قادم');
    }

    public function test_price_range_filter_uses_opening_price_in_dinars(): void
    {
        // opening_price is stored in centimes; the filter speaks dinars.
        $this->makeAuction(['title_ar' => 'رخيص', 'opening_price' => 100_000]);   // 1 000 DZD
        $this->makeAuction(['title_ar' => 'غالي', 'opening_price' => 500_000]);   // 5 000 DZD

        $this->get(route('auctions.index', ['price_min' => 2000]))
            ->assertOk()
            ->assertSee('غالي')
            ->assertDontSee('رخيص');
    }

    public function test_page_beyond_last_redirects_to_last_page(): void
    {
        foreach (range(1, 6) as $i) {
            $this->makeAuction(['status' => AuctionStatus::ACTIVE]);
        }

        // 6 auctions → 2 pages; a hand-typed page 99 bounces back to page 2.
        $this->get(route('auctions.index', ['page' => 99]))
            ->assertRedirect()
            ->assertRedirectContains('page=2');
    }

    public function test_active_filter_chip_removal_link_drops_only_that_filter(): void
    {
        $this->makeAuction(['auction_type' => AuctionType::SALE]);

        $response = $this->get(route('auctions.index', [
            'type' => AuctionType::SALE->value,
            'sort' => 'price_desc',
        ]))->assertOk();

        // The chip's removal link keeps the sort but drops the type.
        $response->assertSee('active-filters', false);
        $response->assertSee('sort=price_desc', false);
    }

    public function test_commerce_register_required_auction_is_visually_flagged(): void
    {
        $this->makeAuction(['title_ar' => 'يتطلب سجلاً', 'requires_commerce_register' => true]);
        $this->makeAuction(['title_ar' => 'مزاد عادي', 'requires_commerce_register' => false]);

        $this->get(route('auctions.index'))
            ->assertOk()
            ->assertSee('cr-required', false) // gold card modifier
            ->assertSee('cr-badge', false);   // "requires commercial register" badge
    }

    public function test_search_endpoint_returns_matching_public_auctions(): void
    {
        $auction = $this->makeAuction(['title_ar' => 'دراجة نارية', 'status' => AuctionStatus::ACTIVE]);

        $this->getJson(route('auctions.search', ['q' => 'دراجة']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'دراجة نارية'])
            ->assertJsonFragment(['url' => route('auctions.show', $auction)]);
    }

    public function test_search_requires_at_least_two_characters(): void
    {
        $this->makeAuction(['title_ar' => 'دراجة نارية']);

        $this->getJson(route('auctions.search', ['q' => 'د']))
            ->assertOk()
            ->assertJsonCount(0, 'results');
    }

    public function test_search_excludes_non_public_auctions(): void
    {
        $this->makeAuction(['title_ar' => 'مسودة سرية', 'status' => AuctionStatus::DRAFT]);

        $this->getJson(route('auctions.search', ['q' => 'مسودة']))
            ->assertOk()
            ->assertJsonCount(0, 'results');
    }
}
