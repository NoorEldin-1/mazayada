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

    public function test_signature_is_bound_to_content(): void
    {
        $doc = $this->makeAward();

        // The signature validates against the untouched document.
        $this->assertTrue(app(DocumentService::class)->verifySignature($doc, $doc->signature));

        // Tampering with a signed fact (the final price in meta) must break it —
        // proving the QR attests the content, not just the document id.
        $meta = $doc->meta;
        $meta['final_price'] = 999_999_999;
        $doc->meta = $meta;

        $this->assertFalse(app(DocumentService::class)->verifySignature($doc, $doc->signature));
    }

    public function test_qr_payload_uses_reachable_base_url_not_localhost(): void
    {
        config()->set('mazayada.documents.qr_verification_base_url', 'https://mazayada.dz/verify');

        $doc = $this->makeAward();

        $this->assertStringStartsWith('https://mazayada.dz/verify?doc=', $doc->qr_payload);
        $this->assertStringNotContainsString('localhost', $doc->qr_payload);
        $this->assertStringNotContainsString('127.0.0.1', $doc->qr_payload);
    }

    public function test_production_ignores_local_lan_override_and_uses_app_url(): void
    {
        // Simulate production: real APP_URL + a leftover local LAN override. The
        // override must be ignored so the QR uses the real domain — no prod env
        // edit required, and a stray dev value can never leak into production.
        config()->set('app.url', 'https://mazayada.dz');
        config()->set('mazayada.documents.qr_verification_base_url', 'http://192.168.1.8:8000/verify');
        \Illuminate\Support\Facades\URL::forceRootUrl('https://mazayada.dz');

        $doc = $this->makeAward();

        $this->assertStringContainsString('://mazayada.dz/verify?doc=', $doc->qr_payload);
        $this->assertStringNotContainsString('192.168.1.8', $doc->qr_payload);
    }

    public function test_local_override_is_used_when_app_url_is_localhost(): void
    {
        config()->set('app.url', 'http://localhost:8000');
        config()->set('mazayada.documents.qr_verification_base_url', 'http://192.168.1.8:8000/verify');

        $doc = $this->makeAward();

        $this->assertStringStartsWith('http://192.168.1.8:8000/verify?doc=', $doc->qr_payload);
    }

    public function test_verify_page_shows_amount_and_signature_reference(): void
    {
        $doc = $this->makeAward();

        $this->get('/verify?doc='.$doc->id.'&sig='.$doc->signature)
            ->assertOk()
            ->assertSee(__('documents.verify.signature_ref'))
            ->assertSee(app(DocumentService::class)->fingerprint($doc->signature))
            ->assertSee(__('documents.verify.amount'));
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
