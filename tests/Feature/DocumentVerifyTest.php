<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Services\DocumentService;
use App\Services\FeeCalculator;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

class DocumentVerifyTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('documents');
    }

    private function makeAward(): Document
    {
        $winner = $this->makeCitizen();
        $auction = $this->makeAuction(['winner_user_id' => $winner->id, 'final_price' => 2_000_000, 'closed_at' => now()]);
        $auction->setRelation('winner', $winner);
        $fees = app(FeeCalculator::class)->forAward($auction, 2_000_000);

        return app(DocumentService::class)->generateAward($auction, $fees);
    }

    public function test_valid_signature_passes_verification(): void
    {
        $doc = $this->makeAward();

        $this->get('/verify?doc='.$doc->id.'&sig='.$doc->signature)
            ->assertOk()
            ->assertSee(__('documents.verify.valid_title'));
    }

    public function test_tampered_signature_is_rejected(): void
    {
        $doc = $this->makeAward();

        $this->get(route('documents.verify', ['doc' => $doc->id, 'sig' => 'tampered']))
            ->assertOk()
            ->assertSee(__('documents.verify.invalid_title'));
    }

    public function test_award_masks_the_winner_nin(): void
    {
        $doc = $this->makeAward();

        // The award stores a masked NIN in meta — the full NIN is never exposed.
        $this->assertArrayHasKey('winner_nin_masked', $doc->meta);
        $this->assertStringContainsString('*', $doc->meta['winner_nin_masked']);
    }

    public function test_download_is_policy_gated(): void
    {
        $doc = $this->makeAward();
        $owner = $doc->user;
        $stranger = $this->makeCitizen();

        $this->actingAs($owner)->get(route('documents.download', $doc))->assertOk();
        $this->actingAs($stranger)->get(route('documents.download', $doc))->assertForbidden();
    }
}
