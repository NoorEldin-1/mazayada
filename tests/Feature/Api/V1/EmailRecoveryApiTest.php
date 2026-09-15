<?php

namespace Tests\Feature\Api\V1;

use App\Enums\EmailRecoveryStatus;
use App\Models\EmailRecoveryRequest;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\ApiTestCase;
use Tests\Concerns\CreatesAuctionData;

class EmailRecoveryApiTest extends ApiTestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('local');
    }

    private function citizen(): User
    {
        return $this->makeCitizen(['phone' => '0555123456', 'birth_date' => '1990-01-01']);
    }

    private function submit(array $payload): TestResponse
    {
        return $this->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/auth/email-recovery', $payload);
    }

    /** @return array<string, mixed> */
    private function payload(User $user, array $overrides = []): array
    {
        return array_merge([
            'nin' => $user->nin,
            'birth_date' => '1990-01-01',
            'phone' => '0555123456',
            'new_email' => 'mohamed.new@gmail.com',
            'selfie_with_id' => UploadedFile::fake()->image('selfie.png', 500, 500)->size(200),
        ], $overrides);
    }

    public function test_submission_returns_201_with_the_masked_request(): void
    {
        $user = $this->citizen();

        $this->submit($this->payload($user))
            ->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'status', 'status_label', 'new_email_masked', 'submitted_at', 'reviewed_at', 'rejection_reason'],
                'message',
                'meta',
            ])
            ->assertJsonPath('data.status', EmailRecoveryStatus::PENDING->value)
            ->assertJsonPath('data.new_email_masked', 'm***@gmail.com')
            ->assertJsonPath('data.reviewed_at', null)
            ->assertJsonPath('data.rejection_reason', null);

        $this->assertSame(1, EmailRecoveryRequest::where('user_id', $user->id)->count());
    }

    public function test_validation_errors_are_keyed_by_field(): void
    {
        $user = $this->citizen();

        $this->submit($this->payload($user, ['birth_date' => '1999-09-09', 'phone' => '0777000000']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['birth_date', 'phone']);

        $this->submit([
            'nin' => '123',
            'birth_date' => '01/01/1990',
            'phone' => '555',
            'new_email' => 'not-an-email',
            'selfie_with_id' => UploadedFile::fake()->create('selfie.pdf', 50, 'application/pdf'),
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nin', 'birth_date', 'phone', 'new_email', 'selfie_with_id']);
    }

    public function test_an_open_request_blocks_a_second_one(): void
    {
        $user = $this->citizen();
        $this->submit($this->payload($user))->assertCreated();

        $this->submit($this->payload($user, ['new_email' => 'other@gmail.com']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nin']);
    }

    public function test_status_is_null_without_a_request_and_returns_the_latest_otherwise(): void
    {
        $user = $this->citizen();

        $this->postJson('/api/v1/auth/email-recovery/status', ['nin' => $user->nin])
            ->assertOk()
            ->assertJsonPath('data', null);

        $this->submit($this->payload($user))->assertCreated();
        EmailRecoveryRequest::where('user_id', $user->id)->update([
            'status' => EmailRecoveryStatus::REJECTED->value,
            'rejection_reason' => 'blurry',
            'reviewed_at' => now(),
        ]);

        $this->postJson('/api/v1/auth/email-recovery/status', ['nin' => $user->nin])
            ->assertOk()
            ->assertJsonPath('data.status', 'REJECTED')
            ->assertJsonPath('data.rejection_reason', 'blurry');

        $this->postJson('/api/v1/auth/email-recovery/status', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nin']);
    }

    public function test_submissions_are_throttled_per_nin(): void
    {
        $user = $this->citizen();

        for ($i = 0; $i < 3; $i++) {
            $this->submit($this->payload($user, ['phone' => '0777000000']))->assertStatus(422);
        }

        $this->submit($this->payload($user))->assertStatus(429);
    }
}
