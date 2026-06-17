<?php

namespace Tests\Feature\Api\V1;

use App\Enums\KycStatus;
use App\Models\Commune;
use App\Models\User;
use App\Models\UserBiometric;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use Tests\Concerns\CreatesAuctionData;

class KycApiTest extends ApiTestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_show_returns_kyc_state(): void
    {
        $user = $this->makeCitizen(['kyc_status' => KycStatus::PENDING, 'kyc_completed_at' => null]);
        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/kyc')
            ->assertOk()
            ->assertJsonPath('data.status', KycStatus::PENDING->value)
            ->assertJsonPath('data.can_submit', true)
            ->assertJsonStructure(['data' => ['documents' => ['id-front', 'id-back', 'selfie-with-id']]]);
    }

    public function test_upload_stores_document_on_private_disk(): void
    {
        $user = $this->makeCitizen(['kyc_status' => KycStatus::PENDING, 'kyc_completed_at' => null]);
        Sanctum::actingAs($user, ['access']);

        $this->postJson('/api/v1/kyc/upload/id-front', [
            'file' => UploadedFile::fake()->image('id.jpg', 600, 400),
        ])->assertOk();

        $bio = UserBiometric::where('user_id', $user->id)->first();
        $this->assertNotNull($bio->id_front_path);
        Storage::disk('local')->assertExists($bio->id_front_path);
    }

    public function test_upload_rejects_unknown_type(): void
    {
        $user = $this->makeCitizen(['kyc_status' => KycStatus::PENDING, 'kyc_completed_at' => null]);
        Sanctum::actingAs($user, ['access']);

        $this->postJson('/api/v1/kyc/upload/passport-scan', [
            'file' => UploadedFile::fake()->image('x.jpg'),
        ])->assertNotFound();
    }

    public function test_submit_requires_all_documents(): void
    {
        $user = $this->makeCitizen(['kyc_status' => KycStatus::PENDING, 'kyc_completed_at' => null]);
        Sanctum::actingAs($user, ['access']);

        $this->postJson('/api/v1/kyc/submit', $this->submitPayload())
            ->assertStatus(422);
    }

    public function test_submit_moves_account_under_review(): void
    {
        $user = $this->makeCitizen(['kyc_status' => KycStatus::PENDING, 'kyc_completed_at' => null]);
        UserBiometric::create([
            'user_id' => $user->id,
            'id_front_path' => 'kyc/'.$user->id.'/front.jpg',
            'id_back_path' => 'kyc/'.$user->id.'/back.jpg',
            'selfie_with_id_path' => 'kyc/'.$user->id.'/selfie.jpg',
        ]);
        Sanctum::actingAs($user, ['access']);

        $this->postJson('/api/v1/kyc/submit', $this->submitPayload())
            ->assertOk()
            ->assertJsonPath('data.status', KycStatus::UNDER_REVIEW->value);

        $this->assertSame(KycStatus::UNDER_REVIEW, $user->fresh()->kyc_status);
    }

    public function test_document_stream_returns_404_when_missing(): void
    {
        $user = $this->makeCitizen(['kyc_status' => KycStatus::PENDING, 'kyc_completed_at' => null]);
        Sanctum::actingAs($user, ['access']);

        $this->get('/api/v1/kyc/document/id-front')->assertNotFound();
    }

    /**
     * @return array<string, mixed>
     */
    private function submitPayload(): array
    {
        $this->refs(); // creates refWilaya (id 40)
        $commune = Commune::create([
            'wilaya_id' => $this->refWilaya->id,
            'code' => '4001',
            'name_ar' => 'خنشلة',
            'name_fr' => 'Khenchela',
            'postal_code' => '40000',
        ]);

        return [
            'first_name_fr' => 'Said',
            'last_name_fr' => 'Benahmed',
            'father_name' => 'Ali',
            'mother_name' => 'Fatima',
            'mother_surname' => 'Cherif',
            'address' => '12 Rue des Frères',
            'wilaya_id' => $this->refWilaya->id,
            'commune_id' => $commune->id,
            'postal_code' => '40000',
        ];
    }
}
