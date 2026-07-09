<?php

namespace Tests\Feature\Api\V1;

use App\Enums\CommercialRegisterStatus;
use App\Models\CommercialRegister;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\ApiTestCase;
use Tests\Concerns\CreatesAuctionData;

class CommercialRegisterApiTest extends ApiTestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('local');
    }

    public function test_show_returns_null_status_when_never_submitted(): void
    {
        $user = $this->makeCitizen();
        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/commercial-register')
            ->assertOk()
            ->assertJsonPath('data.status', null)
            ->assertJsonPath('data.can_submit', true)
            ->assertJsonPath('data.is_valid', false)
            ->assertJsonStructure(['data' => ['documents' => ['register', 'tax-card']]]);
    }

    public function test_show_reflects_an_approved_register(): void
    {
        $user = $this->makeCitizen();
        $this->makeRegister($user, ['status' => CommercialRegisterStatus::APPROVED]);
        Sanctum::actingAs($user, ['access']);

        $this->getJson('/api/v1/commercial-register')
            ->assertOk()
            ->assertJsonPath('data.status', CommercialRegisterStatus::APPROVED->value)
            ->assertJsonPath('data.is_valid', true)
            ->assertJsonPath('data.can_submit', false)
            ->assertJsonPath('data.documents.register', true);
    }

    public function test_store_creates_pending_record_and_stores_scans(): void
    {
        $user = $this->makeCitizen();
        Sanctum::actingAs($user, ['access']);

        $this->postJson('/api/v1/commercial-register', $this->validPayload())
            ->assertOk()
            ->assertJsonPath('data.status', CommercialRegisterStatus::PENDING->value);

        $register = $user->fresh()->commercialRegister;
        $this->assertNotNull($register);
        $this->assertSame(CommercialRegisterStatus::PENDING, $register->status);
        $this->assertSame('شركة الأمل', $register->company_name);
        Storage::disk('local')->assertExists($register->register_document_path);
        Storage::disk('local')->assertExists($register->tax_card_document_path);
    }

    public function test_store_is_locked_while_approved(): void
    {
        $user = $this->makeCitizen();
        $this->makeRegister($user, ['status' => CommercialRegisterStatus::APPROVED]);
        Sanctum::actingAs($user, ['access']);

        $this->postJson('/api/v1/commercial-register', $this->validPayload())
            ->assertForbidden();
    }

    public function test_store_allows_resubmission_after_rejection_without_reuploading(): void
    {
        $user = $this->makeCitizen();
        $this->makeRegister($user, [
            'status' => CommercialRegisterStatus::REJECTED,
            'rejection_reason' => 'الوثيقة غير مقروءة',
        ]);
        Sanctum::actingAs($user, ['access']);

        // Text-only fix: no files sent, existing scans are preserved.
        $payload = $this->validPayload();
        unset($payload['register_document'], $payload['tax_card_document']);

        $this->postJson('/api/v1/commercial-register', $payload)
            ->assertOk()
            ->assertJsonPath('data.status', CommercialRegisterStatus::PENDING->value);

        $this->assertSame(CommercialRegisterStatus::PENDING, $user->fresh()->commercialRegister->status);
    }

    public function test_store_validates_future_expiry(): void
    {
        $user = $this->makeCitizen();
        Sanctum::actingAs($user, ['access']);

        $payload = $this->validPayload();
        $payload['expiry_date'] = now()->subDay()->format('Y-m-d');

        $this->postJson('/api/v1/commercial-register', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('expiry_date');
    }

    public function test_document_returns_404_when_missing(): void
    {
        $user = $this->makeCitizen();
        Sanctum::actingAs($user, ['access']);

        $this->get('/api/v1/commercial-register/document/register')->assertNotFound();
    }

    public function test_document_rejects_unknown_type(): void
    {
        $user = $this->makeCitizen();
        $this->makeRegister($user);
        Sanctum::actingAs($user, ['access']);

        $this->get('/api/v1/commercial-register/document/passport')->assertNotFound();
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/commercial-register')->assertUnauthorized();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'company_name' => 'شركة الأمل',
            'register_number' => '16/00-1234567 A 09',
            'tax_number' => '000116001234567',
            'activity_type' => 'تجارة بالجملة',
            'expiry_date' => now()->addYear()->format('Y-m-d'),
            'register_document' => UploadedFile::fake()->create('register.pdf', 200, 'application/pdf'),
            'tax_card_document' => UploadedFile::fake()->image('tax-card.jpg'),
        ];
    }

    private function makeRegister(User $user, array $overrides = []): CommercialRegister
    {
        return CommercialRegister::create(array_merge([
            'user_id' => $user->id,
            'company_name' => 'شركة تجربة',
            'register_number' => '16/00-0000001 A 09',
            'tax_number' => '000116000000001',
            'activity_type' => 'تجارة',
            'expiry_date' => now()->addYear(),
            'register_document_path' => 'commercial-registers/'.$user->id.'/register.pdf',
            'tax_card_document_path' => 'commercial-registers/'.$user->id.'/tax.jpg',
            'status' => CommercialRegisterStatus::PENDING,
            'submitted_at' => now(),
        ], $overrides));
    }
}
