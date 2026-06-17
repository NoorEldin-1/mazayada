<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\InspectionQuestionStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\AskQuestionRequest;
use App\Http\Resources\Api\V1\QuestionResource;
use App\Models\Auction;
use App\Models\InspectionQuestion;
use Illuminate\Http\JsonResponse;

/**
 * @group Auction Q&A
 *
 * Registered bidders asking written questions during the inspection window
 * (§4 step 4). Answers are published by staff and surfaced via the public Q&A
 * read endpoint.
 */
class QuestionController extends ApiController
{
    /**
     * Ask a question
     *
     * Submit a question for staff to answer. Starts as PENDING and public.
     *
     * @bodyParam question string required The question text (max 1000). Example: هل المركبة تعمل؟
     */
    public function store(AskQuestionRequest $request, Auction $auction): JsonResponse
    {
        $question = InspectionQuestion::create([
            'auction_id' => $auction->id,
            'user_id' => $request->user()->id,
            'question' => $request->validated()['question'],
            'status' => InspectionQuestionStatus::PENDING,
            'is_public' => true,
        ]);

        return $this->created(
            new QuestionResource($question),
            __('inspections.flash_asked'),
        );
    }
}
