<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AppealStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\StoreAppealRequest;
use App\Http\Resources\Api\V1\AppealResource;
use App\Models\Appeal;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Appeals
 *
 * Citizen appeals/complaints, optionally linked to an auction the user took part
 * in.
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
     * Submit an appeal
     *
     * @bodyParam subject string required Example: اعتراض على نتيجة المزاد
     * @bodyParam reason string required Example: لم يتم احتساب مزايدتي الأخيرة.
     * @bodyParam auction_id string An auction the user participated in (optional).
     */
    public function store(StoreAppealRequest $request): JsonResponse
    {
        $appeal = Appeal::create([
            'user_id' => $request->user()->id,
            'auction_id' => $request->input('auction_id'),
            'subject' => $request->input('subject'),
            'reason' => $request->input('reason'),
            'status' => AppealStatus::SUBMITTED,
        ]);

        AuditLog::log('APPEAL_CREATED', 'Appeal', $appeal->id, null, null, [
            'subject' => $request->input('subject'),
        ]);

        return $this->created(new AppealResource($appeal->load('auction')), __('appeals.flash_submitted'));
    }
}
