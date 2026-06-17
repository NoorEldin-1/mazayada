<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\OtpVerificationNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Email OTP issuing + verification, shared by the web AuthController and the
 * mobile API. Single source of truth for the cache-key conventions, TTLs, attempt
 * cap and resend cooldown — so both channels behave identically and interoperate
 * (an OTP issued on the web verifies on the API and vice-versa).
 */
class OtpService
{
    /** Minutes a registration OTP stays valid. */
    public const TTL_REGISTER = 5;

    /** Minutes a password-reset OTP stays valid. */
    public const TTL_RESET = 10;

    /** Seconds the user must wait between "resend code" requests. */
    public const RESEND_COOLDOWN = 60;

    /** Wrong attempts allowed before a fresh code must be requested. */
    public const MAX_ATTEMPTS = 5;

    /**
     * Generate a fresh 6-digit code, cache it for the purpose's TTL, reset the
     * attempt counter, and email it (email-only — no SMS). A mail-transport
     * failure is logged, never thrown, so it cannot turn into a 500.
     */
    public function issue(User $user, string $purpose = 'register', ?int $ttlMinutes = null): void
    {
        $ttlMinutes ??= $this->ttlFor($purpose);
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->otpKey($user->id, $purpose), $otp, now()->addMinutes($ttlMinutes));
        Cache::forget($this->attemptsKey($user->id, $purpose));

        try {
            $user->notify(new OtpVerificationNotification($otp, $ttlMinutes, $purpose));
        } catch (\Throwable $e) {
            Log::error('OTP email failed to send', ['user_id' => $user->id, 'purpose' => $purpose, 'error' => $e->getMessage()]);
        }
    }

    /** Whether the user has exhausted their verification attempts for this purpose. */
    public function tooManyAttempts(string $userId, string $purpose): bool
    {
        return (int) Cache::get($this->attemptsKey($userId, $purpose), 0) >= self::MAX_ATTEMPTS;
    }

    /**
     * Compare a submitted code (timing-safe). On mismatch the attempt counter is
     * incremented; on success the code + counter are cleared so it can't be reused.
     */
    public function verify(string $userId, string $purpose, string $code): bool
    {
        $cached = Cache::get($this->otpKey($userId, $purpose));

        if (! $cached || ! hash_equals($cached, $code)) {
            $attemptsKey = $this->attemptsKey($userId, $purpose);
            Cache::put($attemptsKey, (int) Cache::get($attemptsKey, 0) + 1, now()->addMinutes(15));

            return false;
        }

        Cache::forget($this->otpKey($userId, $purpose));
        Cache::forget($this->attemptsKey($userId, $purpose));

        return true;
    }

    /** Whether a resend is still on cooldown for this user+purpose. */
    public function resendCooldownActive(string $userId, string $purpose): bool
    {
        return Cache::has($this->cooldownKey($userId, $purpose));
    }

    /** Start the resend cooldown window. */
    public function markResent(string $userId, string $purpose): void
    {
        Cache::put($this->cooldownKey($userId, $purpose), true, now()->addSeconds(self::RESEND_COOLDOWN));
    }

    public function ttlFor(string $purpose): int
    {
        return $purpose === 'reset' ? self::TTL_RESET : self::TTL_REGISTER;
    }

    private function otpKey(string $userId, string $purpose): string
    {
        return "otp_{$purpose}_{$userId}";
    }

    private function attemptsKey(string $userId, string $purpose): string
    {
        return "otp_attempts_{$purpose}_{$userId}";
    }

    private function cooldownKey(string $userId, string $purpose): string
    {
        return "otp_resend_cooldown_{$purpose}_{$userId}";
    }
}
