<?php

namespace Tests\Feature\Api\V1;

use App\Enums\DocumentType;
use App\Models\Auction;
use App\Models\Document;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use Tests\Concerns\CreatesAuctionData;

class DocumentLibraryApiTest extends ApiTestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
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
            'file_size' => 4096,
            'mime' => 'application/pdf',
            'signature' => hash('sha256', Str::random()),
            'is_public' => false,
        ], $overrides));
        $doc->id = (string) Str::uuid();
        $doc->save();

        return $doc;
    }

    public function test_index_returns_only_my_documents(): void
    {
        $auction = $this->makeAuction();
        $citizen = $this->makeCitizen();
        $this->makeParticipant($auction, $citizen);
        $mine = $this->makeDocument(DocumentType::AWARD, $auction, $citizen);

        $stranger = $this->makeCitizen();
        $otherAuction = $this->makeAuction();
        $theirs = $this->makeDocument(DocumentType::AWARD, $otherAuction, $stranger);

        Sanctum::actingAs($citizen, ['access']);

        $this->getJson('/api/v1/documents')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'type', 'type_label', 'title', 'file_size_human', 'download_url', 'auction']],
                'meta' => ['pagination' => ['total']],
            ])
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonMissing(['id' => $theirs->id]);
    }

    public function test_type_filter_is_applied(): void
    {
        $auction = $this->makeAuction();
        $citizen = $this->makeCitizen();
        $this->makeParticipant($auction, $citizen);
        $this->makeDocument(DocumentType::CONDITION_BOOK, $auction, null);
        $award = $this->makeDocument(DocumentType::AWARD, $auction, $citizen);

        Sanctum::actingAs($citizen, ['access']);

        $this->getJson('/api/v1/documents?type[]='.DocumentType::AWARD->value)
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.id', $award->id);
    }

    public function test_summary_returns_counts(): void
    {
        $auction = $this->makeAuction();
        $citizen = $this->makeCitizen();
        $this->makeParticipant($auction, $citizen);
        $this->makeDocument(DocumentType::CONDITION_BOOK, $auction, null);
        $this->makeDocument(DocumentType::AWARD, $auction, $citizen);

        Sanctum::actingAs($citizen, ['access']);

        $this->getJson('/api/v1/documents/summary')
            ->assertOk()
            ->assertJsonPath('data.total', 2)
            ->assertJsonPath('data.books', 1)
            ->assertJsonPath('data.awards', 1);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/documents')->assertUnauthorized();
    }
}
