<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreAppealRequest;
use App\Http\Resources\Api\V1\AppealResource;
use App\Models\Appeal;
use App\Models\Auction;
use App\Services\AppealService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Appeals
 *
 * Citizen appeals (الطعون) against an auction result. An appeal is filed from a
 * specific auction the user took part in, then routed through the platform admin
 * and the organising entity. The client only ever sees the 3 public states
 * (PENDING / APPROVED / REJECTED).
 */
class AppealController extends ApiController
{
    /**
     * List my appeals
     */
    public function index(Request $request): JsonResponse
    {
        $appeals = $request->user()->appeals()
            ->with('auction')
            ->latest()
            ->paginate(15);

        return $this->paginated($appeals, AppealResource::class);
    }

    /**
     * Get an appeal
     *
     * Full detail for a single appeal owned by the authenticated user.
     */
    public function show(Request $request, Appeal $appeal): JsonResponse
    {
        abort_unless($appeal->user_id === $request->user()->id, 403);

        $appeal->load('auction');

        return $this->ok(new AppealResource($appeal));
    }

    /**
     * Submit an appeal
     *
     * Files an appeal against the result of the given auction. Requires that the
     * auction has closed, is still within the appeal window, and that the user
     * took part with at least one valid bid; only one appeal per auction.
     *
     * @urlParam auction string required The auction to appeal. Example: a2164134-b1d3-404d-9399-dff76472ac26
     *
     * @bodyParam subject string required Example: اعتراض على نتيجة المزاد
     * @bodyParam reason string required Example: لم يتم احتساب مزايدتي الأخيرة.
     */
    public function store(StoreAppealRequest $request, Auction $auction, AppealService $appeals): JsonResponse
    {
        try {
            $appeal = $appeals->submit(
                $request->user(),
                $auction,
                $request->input('subject'),
                $request->input('reason'),
            );
        } catch (DomainException $e) {
            return $this->fail($e->getMessage(), [], 422);
        }

        return $this->created(new AppealResource($appeal->load('auction')), __('appeals.flash_submitted'));
    }
}
