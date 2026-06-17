<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;

/**
 * @group System
 *
 * Lightweight, unauthenticated endpoints to verify the API pipeline (routing,
 * envelope, locale, rate limiting) is wired correctly.
 */
class HealthController extends ApiController
{
    /**
     * Ping
     *
     * Returns a simple liveness payload wrapped in the standard envelope. Useful
     * for smoke-testing the API plumbing and the active locale.
     *
     * @unauthenticated
     *
     * @response 200 {"data":{"status":"ok","version":"v1","locale":"ar"},"message":"الواجهة البرمجية تعمل.","meta":{}}
     */
    public function ping(): JsonResponse
    {
        return $this->ok([
            'status' => 'ok',
            'version' => 'v1',
            'locale' => app()->getLocale(),
        ], __('common.api.pong'));
    }
}
