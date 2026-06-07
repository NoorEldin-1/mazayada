<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Enums\InspectionQuestionStatus;
use App\Models\Delivery;
use App\Models\InspectionQuestion;
use App\Models\UserNotification;
use App\Services\DeliveryService;
use App\Services\NotificationService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesAuctionData;
use Tests\TestCase;

class InspectionDeliveryTest extends TestCase
{
    use CreatesAuctionData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
        Storage::fake('documents');
    }

    public function test_citizen_can_ask_an_inspection_question(): void
    {
        $auction = $this->makeAuction();
        $user = $this->makeCitizen();

        $this->actingAs($user)
            ->post(route('auctions.questions', $auction), ['question' => 'هل المركبة تعمل؟'])
            ->assertRedirect();

        $this->assertDatabaseHas('inspection_questions', [
            'auction_id' => $auction->id,
            'user_id' => $user->id,
            'status' => 'PENDING',
        ]);
    }

    public function test_answering_a_question_notifies_the_asker(): void
    {
        $auction = $this->makeAuction();
        $asker = $this->makeCitizen();
        $question = InspectionQuestion::create([
            'auction_id' => $auction->id,
            'user_id' => $asker->id,
            'question' => 'سؤال',
            'status' => InspectionQuestionStatus::PENDING,
            'is_public' => true,
        ]);

        $question->update([
            'answer' => 'نعم تعمل',
            'status' => InspectionQuestionStatus::ANSWERED,
        ]);
        app(NotificationService::class)->inspectionAnswered($question->fresh());

        $this->assertDatabaseHas('notifications', ['user_id' => $asker->id, 'channel' => 'IN_APP']);
    }

    public function test_delivery_schedule_and_complete_generates_report(): void
    {
        $winner = $this->makeCitizen();
        $auction = $this->makeAuction(['winner_user_id' => $winner->id, 'final_price' => 2_000_000, 'closed_at' => now()]);

        $service = app(DeliveryService::class);
        $delivery = $service->schedule($auction, ['scheduled_at' => now()->addDays(2)->toDateTimeString(), 'address' => 'مستودع البلدية']);

        $this->assertSame(DeliveryStatus::SCHEDULED, $delivery->status);

        $service->markDelivered($delivery);

        $delivery->refresh();
        $this->assertSame(DeliveryStatus::DELIVERED, $delivery->status);
        $this->assertNotNull($delivery->report_document_id);
        $this->assertDatabaseHas('documents', ['id' => $delivery->report_document_id, 'type' => 'DELIVERY_REPORT']);
    }
}
