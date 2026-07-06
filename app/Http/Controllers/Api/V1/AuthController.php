<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\RegisterCitizen;
use App\Enums\AccountStatus;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RecoverBySecretRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\RequestPasswordResetRequest;
use App\Http\Requests\Api\V1\Auth\ResendOtpRequest;
use App\Http\Requests\Api\V1\Auth\RevealSecretQuestionRequest;
use App\Http\Requests\Api\V1\Auth\VerifyOtpRequest;
use App\Http\Requests\Api\V1\Auth\VerifyPasswordResetRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Api\TokenService;
use App\Services\Auth\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 *
 * Token-based authentication for the mobile app. Mirrors the web auth flows
 * (OTP email verification, enumeration-safe reset, secret-question recovery) but
 * issues a Sanctum access + refresh token pair instead of a session. The shared
 * OTP / user-creation logic lives in OtpService + RegisterCitizen so web and API
 * stay in lock-step.
 */
class AuthController extends ApiController
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly TokenService $tokens,
    ) {}

    /**
     * Register
     *
     * Create a CITIZEN account and email a 6-digit verification code. Does NOT log
     * the user in — call verify-otp with the returned user_id to obtain tokens.
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request, RegisterCitizen $register): JsonResponse
    {
        $user = $register->create($request->validated(), app()->getLocale());

        $this->otp->issue($user, 'register');

        return $this->created(
            ['user_id' => $user->id],
            __('auth.api.registered'),
        );
    }

    /**
     * Verify OTP
     *
     * Verify the registration code and receive an access + refresh token pair.
     *
     * @unauthenticated
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $userId = (string) $request->input('user_id');

        if ($this->otp->tooManyAttempts($userId, 'register')) {
            throw ValidationException::withMessages(['otp' => __('auth.otp_too_many_attempts')]);
        }

        if (! $this->otp->verify($userId, 'register', (string) $request->input('otp'))) {
            throw ValidationException::withMessages(['otp' => __('auth.otp_invalid')]);
        }

        $user = User::findOrFail($userId);
        $user->update(['email_verified' => true]);

        AuditLog::log('OTP_VERIFIED', 'User', $user->id);

        return $this->ok(
            $this->authPayload($user, $request->input('device_name')),
            __('auth.api.email_verified'),
        );
    }

    /**
     * Resend OTP
     *
     * Re-issue a fresh registration code, throttled by a 60-second cooldown.
     *
     * @unauthenticated
     */
    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $userId = (string) $request->input('user_id');

        if ($this->otp->resendCooldownActive($userId, 'register')) {
            throw ValidationException::withMessages([
                'otp' => __('auth.otp_resend_cooldown', ['sec' => OtpService::RESEND_COOLDOWN]),
            ]);
        }

        $this->otp->issue(User::findOrFail($userId), 'register');
        $this->otp->markResent($userId, 'register');

        return $this->ok(null, __('auth.otp_resent'));
    }

    /**
     * Login
     *
     * Authenticate with NIN-or-email + password. Returns a token pair, or — if the
     * email is unverified — `data.needs_email_verification = true` and a fresh OTP.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $maxAttempts = (int) config('mazayada.security.login_max_attempts', 5);
        $decayMinutes = (int) config('mazayada.security.login_decay_minutes', 15);

        $field = filter_var($request->input('nin_or_email'), FILTER_VALIDATE_EMAIL) ? 'email' : 'nin';
        $user = User::where($field, $request->input('nin_or_email'))->first();

        if ($user && $user->isLocked()) {
            throw ValidationException::withMessages([
                'nin_or_email' => __('auth.account_locked', ['time' => $user->locked_until->diffForHumans()]),
            ]);
        }

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            if ($user) {
                $attempts = ($user->failed_login_attempts ?? 0) + 1;
                $update = ['failed_login_attempts' => $attempts];

                if ($attempts >= $maxAttempts) {
                    $update['locked_until'] = now()->addMinutes($decayMinutes);
                    $update['failed_login_attempts'] = 0;
                }

                $user->update($update);
            }

            AuditLog::log('LOGIN_FAILED', 'User', $user?->id ?? 'unknown', null, null, [
                'input' => $request->input('nin_or_email'),
                'ip' => $request->ip(),
                'channel' => 'api',
            ]);

            throw ValidationException::withMessages(['nin_or_email' => __('auth.invalid_credentials')]);
        }

        if ($user->isBlacklisted()) {
            AuditLog::log('LOGIN_BLOCKED', 'User', $user->id, $user->id, null, ['reason' => 'blacklisted']);

            throw ValidationException::withMessages(['nin_or_email' => __('auth.account_blocked')]);
        }

        if (in_array($user->account_status, [AccountStatus::SUSPENDED, AccountStatus::BANNED], true)) {
            AuditLog::log('LOGIN_BLOCKED', 'User', $user->id, $user->id, null, ['reason' => 'account_'.$user->account_status->value]);

            throw ValidationException::withMessages(['nin_or_email' => __('auth.account_blocked')]);
        }

        // Credentials valid but email never verified — issue a fresh code and tell
        // the client to route through the OTP screen.
        if (! $user->email_verified) {
            $this->otp->issue($user, 'register');

            return $this->ok(
                ['needs_email_verification' => true, 'user_id' => $user->id],
                __('auth.api.needs_email_verification'),
            );
        }

        $user->update(['failed_login_attempts' => 0, 'locked_until' => null]);

        AuditLog::log('LOGIN_SUCCESS', 'User', $user->id, $user->id, $user->role?->value, [
            'ip' => $request->ip(),
            'channel' => 'api',
        ]);

        return $this->ok(
            $this->authPayload($user, $request->input('device_name')),
            __('auth.api.logged_in'),
        );
    }

    /**
     * Refresh tokens
     *
     * Exchange a valid refresh token for a new access + refresh pair (rotation).
     * Send the REFRESH token as the bearer here, not the access token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $tokens = $this->tokens->rotate($user, $user->currentAccessToken());

        return $this->ok(['tokens' => $tokens], __('auth.api.token_refreshed'));
    }

    /**
     * Logout
     *
     * Revoke the current device's token pair. Pass `all=true` to revoke every
     * device.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->boolean('all')) {
            $this->tokens->revokeAll($user);
        } else {
            $this->tokens->revokeDevice($user, $user->currentAccessToken());
        }

        AuditLog::log('LOGOUT', 'User', $user->id, $user->id, null, ['channel' => 'api']);

        return $this->ok(null, __('auth.api.logged_out'));
    }

    /**
     * Current user
     *
     * Return the authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->ok(['user' => new UserResource($request->user()->load('entity'))]);
    }

    /**
     * Request password reset
     *
     * Step 1 — email a reset code if the NIN+email pair matches an account. The
     * response is always neutral (does not reveal whether the account exists).
     *
     * @unauthenticated
     */
    public function requestPasswordReset(RequestPasswordResetRequest $request): JsonResponse
    {
        $user = User::where('nin', $request->input('nin'))
            ->where('email', $request->input('email'))
            ->first();

        if ($user) {
            $this->otp->issue($user, 'reset');
        }

        return $this->ok(null, __('auth.reset_otp_sent'));
    }

    /**
     * Verify password reset
     *
     * Step 2 — verify the reset code and set a new password. All existing tokens
     * and sessions are revoked.
     *
     * @unauthenticated
     */
    public function verifyPasswordReset(VerifyPasswordResetRequest $request): JsonResponse
    {
        $user = User::where('nin', $request->input('nin'))
            ->where('email', $request->input('email'))
            ->first();

        if ($user && $this->otp->tooManyAttempts($user->id, 'reset')) {
            throw ValidationException::withMessages(['otp' => __('auth.otp_too_many_attempts')]);
        }

        if (! $user || ! $this->otp->verify($user->id, 'reset', (string) $request->input('otp'))) {
            throw ValidationException::withMessages(['otp' => __('auth.otp_invalid')]);
        }

        $user->update(['password' => $request->input('password')]);

        invalidate_user_sessions($user->id);
        $this->tokens->revokeAll($user);

        AuditLog::log('PASSWORD_RESET', 'User', $user->id);

        return $this->ok(null, __('auth.password_changed'));
    }

    /**
     * Reveal secret question
     *
     * Step 1 of account recovery — return the account's security question.
     *
     * @unauthenticated
     */
    public function revealSecretQuestion(RevealSecretQuestionRequest $request): JsonResponse
    {
        $user = User::where('nin', $request->input('nin'))
            ->where('email', $request->input('email'))
            ->whereNotNull('secret_question')
            ->whereNotNull('secret_answer')
            ->first();

        if (! $user) {
            throw ValidationException::withMessages(['nin' => __('auth.recover_not_available')]);
        }

        return $this->ok(['question' => $user->secret_question], __('auth.api.question_revealed'));
    }

    /**
     * Recover by secret answer
     *
     * Step 2 of account recovery — verify the secret answer and set a new
     * password. All existing tokens and sessions are revoked.
     *
     * @unauthenticated
     */
    public function recoverBySecret(RecoverBySecretRequest $request): JsonResponse
    {
        $user = User::where('nin', $request->input('nin'))
            ->where('email', $request->input('email'))
            ->whereNotNull('secret_answer')
            ->first();

        if (! $user || ! Hash::check($request->input('secret_answer'), $user->secret_answer)) {
            throw ValidationException::withMessages(['secret_answer' => __('auth.recover_wrong_answer')]);
        }

        $user->update(['password' => $request->input('password')]);

        invalidate_user_sessions($user->id);
        $this->tokens->revokeAll($user);

        AuditLog::log('PASSWORD_RECOVERED_SECRET', 'User', $user->id);

        return $this->ok(null, __('auth.password_changed'));
    }

    /**
     * Build the standard authenticated payload: the user resource + a fresh token
     * pair for the device.
     *
     * @return array{user: UserResource, tokens: array<string, mixed>}
     */
    private function authPayload(User $user, ?string $deviceName): array
    {
        return [
            'user' => new UserResource($user),
            'tokens' => $this->tokens->issuePair($user, $deviceName),
        ];
    }
}
