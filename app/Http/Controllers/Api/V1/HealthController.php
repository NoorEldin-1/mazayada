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
     * Liveness payload plus the settings the client needs to bootstrap itself:
     * the active locale and the PUBLIC realtime (Reverb) connection parameters.
     *
     * Fetch this at startup and configure the websocket from `realtime` rather
     * than hardcoding a host per build — the production socket endpoint differs
     * from the dev one, and it can change without an app release.
     *
     * `realtime` is null when broadcasting is not configured; fall back to
     * polling `GET auctions/{auction}/price` and `.../bids` in that case.
     *
     * SECURITY: only the public app key is published here. The Reverb app SECRET
     * is server-side only and is never exposed. Subscribing to a private channel
     * still requires POST /api/broadcasting/auth with the bearer token.
     *
     * @unauthenticated
     *
     * @response 200 {"data":{"status":"ok","version":"v1","locale":"ar","realtime":{"driver":"reverb","key":"mazayada","host":"mazayada.findosystem.com","port":443,"scheme":"https","auth_endpoint":"https://mazayada.findosystem.com/api/broadcasting/auth"}},"message":"الواجهة البرمجية تعمل.","meta":{}}
     */
    public function ping(): JsonResponse
    {
        return $this->ok([
            'status' => 'ok',
            'version' => 'v1',
            'locale' => app()->getLocale(),
            'realtime' => $this->realtimeConfig(),
        ], __('common.api.pong'));
    }

    /**
     * The browser/app-facing half of the Reverb connection — the same values the
     * web client gets from partials/ws-config.blade.php, read from the `client`
     * block so the public wss endpoint stays decoupled from the internal host the
     * server publishes to.
     *
     * @return array<string, mixed>|null
     */
    private function realtimeConfig(): ?array
    {
        if (config('broadcasting.default') !== 'reverb') {
            return null;
        }

        $key = config('broadcasting.connections.reverb.client.key');
        $host = config('broadcasting.connections.reverb.client.host');

        if (blank($key) || blank($host)) {
            return null;
        }

        return [
            'driver' => 'reverb',
            'key' => (string) $key,
            'host' => (string) $host,
            'port' => (int) config('broadcasting.connections.reverb.client.port', 443),
            'scheme' => (string) config('broadcasting.connections.reverb.client.scheme', 'https'),
            // Private channels (auction.{id}.user.{id}) authorise here with the
            // Sanctum access token — NOT at the web /broadcasting/auth.
            'auth_endpoint' => url('/api/broadcasting/auth'),
        ];
    }
}
