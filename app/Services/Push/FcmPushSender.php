<?php

namespace App\Services\Push;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Firebase Cloud Messaging HTTP v1 sender.
 *
 * v1 replaced the legacy server-key API (shut down in 2024): requests are
 * authorised with a short-lived OAuth2 access token minted from a Google service
 * account, and there is no multicast endpoint — each token is its own request.
 *
 * Going live is credentials-only: drop the service-account JSON on the server and
 * set PUSH_DRIVER=fcm + FCM_CREDENTIALS. With no credentials the resolver picks
 * LogPushSender instead, so nothing here runs unconfigured.
 *
 * Never throws: a provider outage must not fail the bid/payment request that
 * triggered the notification. Failures are logged; permanently invalid tokens are
 * returned so PushChannel can prune them.
 */
class FcmPushSender implements PushSender
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    /** Google mints 1-hour tokens; refresh a little early. */
    private const TOKEN_TTL = 3300;

    public function send(array $tokens, PushMessage $message): array
    {
        $credentials = $this->credentials();

        if ($credentials === null || $tokens === []) {
            return [];
        }

        $accessToken = $this->accessToken($credentials);

        if ($accessToken === null) {
            return [];
        }

        $url = sprintf('https://fcm.googleapis.com/v1/projects/%s/messages:send', $credentials['project_id']);
        $stale = [];

        foreach ($tokens as $token) {
            try {
                $response = Http::withToken($accessToken)
                    ->acceptJson()
                    ->timeout(10)
                    ->post($url, ['message' => $this->payload($token, $message)]);

                if ($response->successful()) {
                    continue;
                }

                if ($this->isPermanentlyInvalid($response->status(), (array) $response->json())) {
                    $stale[] = $token;

                    continue;
                }

                Log::warning('FCM send failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
            } catch (Throwable $e) {
                Log::warning('FCM send threw', ['error' => $e->getMessage()]);
            }
        }

        return $stale;
    }

    /**
     * One FCM v1 message. `data` is duplicated alongside `notification` so the app
     * can deep-link both when it is foregrounded (data handler) and when the OS
     * displays the notification and the user taps it.
     *
     * @return array<string, mixed>
     */
    private function payload(string $token, PushMessage $message): array
    {
        return [
            'token' => $token,
            'notification' => [
                'title' => $message->title,
                'body' => $message->body,
            ],
            'data' => $message->stringData(),
            'android' => [
                'priority' => 'high',
                'notification' => ['sound' => 'default'],
            ],
            'apns' => [
                'payload' => ['aps' => ['sound' => 'default']],
            ],
        ];
    }

    /**
     * FCM reports a dead token as 404 UNREGISTERED, or 400 INVALID_ARGUMENT when
     * the string is not a token at all. Anything else (429, 5xx) is transient and
     * must NOT delete the device row.
     *
     * @param  array<string, mixed>  $body
     */
    private function isPermanentlyInvalid(int $status, array $body): bool
    {
        if ($status === 404) {
            return true;
        }

        return $status === 400
            && str_contains((string) data_get($body, 'error.status'), 'INVALID_ARGUMENT');
    }

    /**
     * Exchange the service account for an OAuth2 access token, cached until just
     * before it expires so a burst of notifications mints one token, not N.
     *
     * @param  array<string, mixed>  $credentials
     */
    private function accessToken(array $credentials): ?string
    {
        $cacheKey = 'fcm:access_token:'.md5((string) $credentials['client_email']);

        return Cache::remember($cacheKey, self::TOKEN_TTL, function () use ($credentials): ?string {
            try {
                $assertion = $this->jwtAssertion($credentials);

                if ($assertion === null) {
                    return null;
                }

                $response = Http::asForm()->timeout(10)->post(self::TOKEN_URL, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $assertion,
                ]);

                if (! $response->successful()) {
                    Log::error('FCM OAuth token request failed', ['body' => $response->json()]);

                    return null;
                }

                return $response->json('access_token');
            } catch (Throwable $e) {
                Log::error('FCM OAuth token request threw', ['error' => $e->getMessage()]);

                return null;
            }
        });
    }

    /** Build and RS256-sign the JWT bearer assertion for the token exchange. */
    private function jwtAssertion(array $credentials): ?string
    {
        $now = time();

        $segments = [
            $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])),
            $this->base64Url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => self::SCOPE,
                'aud' => self::TOKEN_URL,
                'iat' => $now,
                'exp' => $now + 3600,
            ])),
        ];

        $signature = '';
        $signed = openssl_sign(implode('.', $segments), $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);

        if (! $signed) {
            Log::error('FCM JWT signing failed — check the service account private_key');

            return null;
        }

        $segments[] = $this->base64Url($signature);

        return implode('.', $segments);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * The service-account JSON, read once per request. Returns null (and the
     * sender becomes a no-op) when the file is missing or malformed.
     *
     * @return array{project_id: string, client_email: string, private_key: string}|null
     */
    private function credentials(): ?array
    {
        static $cached = false;
        static $value = null;

        if ($cached) {
            return $value;
        }

        $cached = true;
        $path = (string) config('mazayada.push.fcm.credentials');

        if ($path === '' || ! is_file($path)) {
            Log::warning('FCM credentials file not found', ['path' => $path]);

            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);

        if (! is_array($json) || ! isset($json['project_id'], $json['client_email'], $json['private_key'])) {
            Log::error('FCM credentials file is not a valid service account JSON', ['path' => $path]);

            return null;
        }

        return $value = $json;
    }
}
