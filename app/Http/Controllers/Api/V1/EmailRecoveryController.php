<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Auth\EmailRecoveryStatusRequest;
use App\Http\Requests\Api\V1\Auth\SubmitEmailRecoveryRequest;
use App\Http\Resources\Api\V1\EmailRecoveryResource;
use App\Services\EmailRecoveryService;
use Illuminate\Http\JsonResponse;

/**
 * @group Authentication
 *
 * "Lost my email" recovery for a citizen who can no longer reach the mailbox on
 * their account. The request is reviewed by an admin; on approval the account
 * email is replaced and every session and token is revoked. Same rules as the
 * web page (EmailRecoveryService).
 */
class EmailRecoveryController extends ApiController
{
    public function __construct(private readonly EmailRecoveryService $recovery) {}

    /**
     * Submit an email recovery request
     *
     * Multipart. NIN, birth date and phone must match the account. Only one open
     * request per account; throttled to 3 per NIN and 10 per IP per hour.
     *
     * @unauthenticated
     *
     * @bodyParam nin string required The 18-digit NIN of the account. Example: 109823041175663801
     * @bodyParam birth_date string required Birth date on the account (Y-m-d). Example: 1990-01-01
     * @bodyParam phone string required Phone on the account (10 digits starting with 0). Example: 0555123456
     * @bodyParam new_email string required The new email address (not used by another account). Example: new.me@gmail.com
     * @bodyParam selfie_with_id file required A selfie holding the national ID card (JPG/PNG, max 1024 KB).
     */
    public function store(SubmitEmailRecoveryRequest $request): JsonResponse
    {
        $recovery = $this->recovery->submit(
            $request->safe()->only(['nin', 'birth_date', 'phone', 'new_email']),
            $request->file('selfie_with_id'),
            $request->ip(),
        );

        return $this->created(new EmailRecoveryResource($recovery), __('email_recovery.api.submitted'));
    }

    /**
     * Email recovery status
     *
     * The latest recovery request filed for a NIN. `data` is null when none exists.
     *
     * @unauthenticated
     *
     * @bodyParam nin string required The 18-digit NIN. Example: 109823041175663801
     */
    public function status(EmailRecoveryStatusRequest $request): JsonResponse
    {
        $recovery = $this->recovery->latestForNin($request->string('nin')->toString());

        return $this->ok($recovery ? new EmailRecoveryResource($recovery) : null);
    }
}
