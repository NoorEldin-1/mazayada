<?php

namespace App\Services\Api;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Issues, rotates and revokes the access + refresh token pairs used by the mobile
 * API. Sanctum has no native refresh flow, so we model it with two tokens:
 *
 *   - access  : ability ['access'], short-lived (config api.access_ttl_minutes)
 *   - refresh : ability ['refresh'], long-lived (config api.refresh_ttl_days)
 *
 * The two abilities are deliberately disjoint so an access token can NOT call the
 * refresh endpoint and a refresh token can NOT call normal endpoints. Both tokens
 * of a device share a device id embedded in their name ("access#<id>#<label>")
 * so rotation/logout can delete exactly the matching pair.
 */
class TokenService
{
    /**
     * Mint a fresh access + refresh pair for a device.
     *
     * @return array{access_token:string,refresh_token:string,token_type:string,expires_in:int,refresh_expires_in:int}
     */
    public function issuePair(User $user, ?string $deviceName = null, ?string $deviceId = null): array
    {
        $deviceId ??= (string) Str::uuid();
        $label = $deviceName ? Str::limit($deviceName, 80, '') : 'mobile';

        $accessMinutes = (int) setting('api.access_ttl_minutes', 60);
        $refreshDays = (int) setting('api.refresh_ttl_days', 30);

        $access = $user->createToken("access#{$deviceId}#{$label}", ['access'], now()->addMinutes($accessMinutes));
        $refresh = $user->createToken("refresh#{$deviceId}#{$label}", ['refresh'], now()->addDays($refreshDays));

        return [
            'access_token' => $access->plainTextToken,
            'refresh_token' => $refresh->plainTextToken,
            'token_type' => 'Bearer',
            'expires_in' => $accessMinutes * 60,
            'refresh_expires_in' => $refreshDays * 24 * 60 * 60,
        ];
    }

    /**
     * Rotate a device's pair: delete the current access+refresh tokens and mint a
     * new pair under the same device id. Serialised by a per-device cache lock and
     * wrapped in a transaction so two concurrent refreshes can't both succeed.
     *
     * @return array{access_token:string,refresh_token:string,token_type:string,expires_in:int,refresh_expires_in:int}
     */
    public function rotate(User $user, PersonalAccessToken $currentRefresh): array
    {
        $deviceId = $this->deviceIdFromName($currentRefresh->name) ?? (string) Str::uuid();
        $label = $this->labelFromName($currentRefresh->name);

        $lock = Cache::lock("token-rotate:{$user->id}:{$deviceId}", 5);

        return $lock->block(5, function () use ($user, $deviceId, $label) {
            return DB::transaction(function () use ($user, $deviceId, $label) {
                $user->tokens()
                    ->where(function ($q) use ($deviceId) {
                        $q->where('name', 'like', "access#{$deviceId}#%")
                            ->orWhere('name', 'like', "refresh#{$deviceId}#%");
                    })
                    ->delete();

                return $this->issuePair($user, $label, $deviceId);
            });
        });
    }

    /** Revoke every token for the user (logout-all / password change / blacklist). */
    public function revokeAll(User $user): void
    {
        $user->tokens()->delete();
    }

    /** Revoke just the current device's pair (normal logout). */
    public function revokeDevice(User $user, ?PersonalAccessToken $currentAccess): void
    {
        if (! $currentAccess) {
            return;
        }

        $deviceId = $this->deviceIdFromName($currentAccess->name);

        if ($deviceId === null) {
            // Token wasn't issued by issuePair (e.g. a test token) — drop just it.
            $currentAccess->delete();

            return;
        }

        $user->tokens()
            ->where(function ($q) use ($deviceId) {
                $q->where('name', 'like', "access#{$deviceId}#%")
                    ->orWhere('name', 'like', "refresh#{$deviceId}#%");
            })
            ->delete();
    }

    private function deviceIdFromName(string $name): ?string
    {
        $parts = explode('#', $name);

        return $parts[1] ?? null;
    }

    private function labelFromName(string $name): ?string
    {
        $parts = explode('#', $name);

        return $parts[2] ?? null;
    }
}
