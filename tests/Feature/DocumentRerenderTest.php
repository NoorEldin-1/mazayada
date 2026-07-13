<?php

namespace Tests\Feature;

use App\Services\DocumentService;
use App\Services\FeeCalculator;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

class DocumentRerenderTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('documents');
    }

    public function test_dzd_pdf_keeps_the_amount_as_one_ltr_run(): void
    {
        app()->setLocale('ar');

        // The mpdf money helper must wrap the whole grouped number in a single
        // dir="ltr" span so mpdf's bidi cannot reorder the "200 000" groups into
        // "000 200". This is the guard for the RTL money-reversal regression.
        $html = (string) dzd_pdf(20_000_000); // 200,000 دج

        $this->assertStringContainsString('<span dir="ltr">200 000</span>', $html);
        $this->assertStringNotContainsString('000 200', $html);
    }

    public function test_rerender_preserves_identity_and_refreshes_the_file(): void
    {
        $winner = $this->makeCitizen();
        $auction = $this->makeAuction(['winner_user_id' => $winner->id, 'final_price' => 2_000_000, 'closed_at' => now()]);
        $auction->setRelation('winner', $winner);
        $fees = app(FeeCalculator::class)->forAward($auction, 2_000_000);

        $service = app(DocumentService::class);
        $doc = $service->generateAward($auction, $fees);

        $originalId = $doc->id;
        $originalSig = $doc->signature;
        $originalMeta = $doc->meta;
        $originalPath = $doc->file_path;

        // Corrupt the stored binary to prove the re-render actually rewrites it.
        Storage::disk('documents')->put($originalPath, 'CORRUPT');

        $this->assertTrue($service->rerender($doc->fresh()));

        $fresh = $doc->fresh();
        // Identity is untouched — this is a re-render, not a re-issue.
        $this->assertSame($originalId, $fresh->id);
        $this->assertSame($originalSig, $fresh->signature);
        $this->assertEquals($originalMeta, $fresh->meta);
        $this->assertSame($originalPath, $fresh->file_path);

        // The file was rewritten with a real PDF (not the corrupt marker).
        $bytes = Storage::disk('documents')->get($originalPath);
        $this->assertStringStartsWith('%PDF', $bytes);
        $this->assertGreaterThan(1000, strlen($bytes));

        // The signature still verifies (it attests content, not the PDF bytes).
        $this->assertTrue($service->verifySignature($fresh, $fresh->signature));
    }
}
