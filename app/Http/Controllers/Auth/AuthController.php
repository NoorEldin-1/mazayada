<?php

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\OtpVerificationNotification;
use App\Rules\AlgerianPhone;
use App\Rules\NinValidation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    /** Minutes a registration OTP stays valid. */
    private const OTP_TTL_MINUTES = 5;

    /** Minutes a password-reset OTP stays valid. */
    private const OTP_RESET_TTL_MINUTES = 10;

    /** Seconds the user must wait between "resend code" requests. */
    private const OTP_RESEND_COOLDOWN = 60;

    /** Wrong attempts allowed before a fresh code must be requested. */
    private const OTP_MAX_ATTEMPTS = 5;

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'nin_or_email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::lower($request->input('nin_or_email')).'|'.$request->ip();
        $maxAttempts = (int) config('mazayada.security.login_max_attempts', 5);
        $decayMinutes = (int) config('mazayada.security.login_decay_minutes', 15);

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'nin_or_email' => __('auth.too_many_attempts', ['sec' => $seconds]),
            ]);
        }

        $field = filter_var($request->nin_or_email, FILTER_VALIDATE_EMAIL) ? 'email' : 'nin';
        $user = User::where($field, $request->nin_or_email)->first();

        if ($user && $user->isLocked()) {
            return back()->withErrors([
                'nin_or_email' => __('auth.account_locked', ['time' => $user->locked_until->diffForHumans()]),
            ]);
        }

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, $decayMinutes * 60);

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
                'input' => $request->nin_or_email,
                'ip' => $request->ip(),
            ]);

            return back()->withErrors(['nin_or_email' => __('auth.invalid_credentials')])->onlyInput('nin_or_email');
        }

        if ($user->isBlacklisted()) {
            AuditLog::log('LOGIN_BLOCKED', 'User', $user->id, $user->id, null, ['reason' => 'blacklisted']);

            return back()->withErrors(['nin_or_email' => __('auth.account_blocked')]);
        }

        // A deactivated/suspended account (e.g. an entity staff member turned off
        // by their head) cannot log in. NOTE: a stale-KYC suspension only sets
        // kyc_status (account_status stays ACTIVE), so those users can still log
        // in and view the platform — they just cannot bid/pay (spec §3.3).
        if (in_array($user->account_status, [AccountStatus::SUSPENDED, AccountStatus::BANNED], true)) {
            AuditLog::log('LOGIN_BLOCKED', 'User', $user->id, $user->id, null, ['reason' => 'account_'.$user->account_status->value]);

            return back()->withErrors(['nin_or_email' => __('auth.account_blocked')]);
        }

        // Credentials are valid but the email was never verified — route the
        // user back through the OTP screen with a fresh code instead of
        // letting them in. Closes the "register then skip verification" gap.
        if (! $user->email_verified) {
            RateLimiter::clear($throttleKey);
            $this->issueOtp($user);

            return redirect()->route('verify-otp')
                ->with('user_id', $user->id)
                ->with('status', __('auth.verify_email_first'));
        }

        RateLimiter::clear($throttleKey);
        $user->update(['failed_login_attempts' => 0, 'locked_until' => null]);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        AuditLog::log('LOGIN_SUCCESS', 'User', $user->id, $user->id, $user->role?->value, [
            'ip' => $request->ip(),
            'ua' => substr((string) $request->userAgent(), 0, 200),
        ]);

        if ($user->isStaff()) {
            return redirect()->intended(route('admin.dashboard'));
        }

        return redirect()->intended(route('citizen.dashboard'));
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nin' => ['required', 'string', new NinValidation, 'unique:users,nin'],
            'first_name_ar' => ['required', 'string', 'max:100'],
            'last_name_ar' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', new AlgerianPhone, 'unique:users,phone'],
            // RFC-only — DNS check would add 100-500ms latency and we verify via OTP anyway.
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'birth_date' => ['required', 'date', 'before:'.now()->subYears(18)->toDateString()],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'nin' => $validated['nin'],
            'first_name_ar' => $validated['first_name_ar'],
            'last_name_ar' => $validated['last_name_ar'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'birth_date' => $validated['birth_date'],
            'password' => $validated['password'],
            'role' => UserRole::CITIZEN,
            // Carry over the language the guest picked on the landing page so
            // their account is created in the language they were browsing in.
            'locale' => session('locale', config('locales.default', 'ar')),
        ]);

        $user->assignRole(UserRole::CITIZEN->value);

        $this->issueOtp($user);

        return redirect()->route('verify-otp')->with('user_id', $user->id);
    }

    public function showVerifyOtp(): View
    {
        return view('auth.verify-otp');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        // ===== Resend branch — re-issue a fresh code, throttled by a cooldown. =====
        if ($request->boolean('resend')) {
            $request->validate(['user_id' => ['required', 'exists:users,id']]);

            $cooldownKey = "otp_resend_cooldown_register_{$request->user_id}";
            if (Cache::has($cooldownKey)) {
                return back()
                    ->with('user_id', $request->user_id)
                    ->withErrors(['otp' => __('auth.otp_resend_cooldown', ['sec' => self::OTP_RESEND_COOLDOWN])]);
            }

            $user = User::findOrFail($request->user_id);
            $this->issueOtp($user);
            Cache::put($cooldownKey, true, now()->addSeconds(self::OTP_RESEND_COOLDOWN));

            return back()->with(['user_id' => $user->id, 'status' => __('auth.otp_resent')]);
        }

        // ===== Verify branch =====
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $attemptsKey = "otp_attempts_register_{$request->user_id}";

        if ((int) Cache::get($attemptsKey, 0) >= self::OTP_MAX_ATTEMPTS) {
            return back()
                ->with('user_id', $request->user_id)
                ->withErrors(['otp' => __('auth.otp_too_many_attempts')]);
        }

        $cachedOtp = Cache::get("otp_register_{$request->user_id}");

        if (! $cachedOtp || ! hash_equals($cachedOtp, $request->otp)) {
            Cache::put($attemptsKey, (int) Cache::get($attemptsKey, 0) + 1, now()->addMinutes(10));

            return back()
                ->with('user_id', $request->user_id)
                ->withErrors(['otp' => __('auth.otp_invalid')]);
        }

        $user = User::findOrFail($request->user_id);
        // Email-only verification — phone is verified separately (not yet wired).
        $user->update(['email_verified' => true]);

        Cache::forget("otp_register_{$user->id}");
        Cache::forget($attemptsKey);
        Auth::login($user);

        AuditLog::log('OTP_VERIFIED', 'User', $user->id);

        return redirect()->route('citizen.dashboard');
    }

    /**
     * Generate a fresh 6-digit code, cache it for OTP_TTL_MINUTES, and email it
     * to the user (email-only — no SMS). Resets any previous attempt counter.
     */
    private function issueOtp(User $user, string $purpose = 'register', ?int $ttlMinutes = null): void
    {
        $ttlMinutes ??= self::OTP_TTL_MINUTES;
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put("otp_{$purpose}_{$user->id}", $otp, now()->addMinutes($ttlMinutes));
        Cache::forget("otp_attempts_{$purpose}_{$user->id}");

        // Never let a mail-transport failure (bad SMTP creds, network blip) turn
        // into a 500 — the code is cached and the user can hit "resend" once mail
        // is configured. The failure is logged for ops.
        try {
            $user->notify(new OtpVerificationNotification($otp, $ttlMinutes, $purpose));
        } catch (\Throwable $e) {
            Log::error('OTP email failed to send', ['user_id' => $user->id, 'purpose' => $purpose, 'error' => $e->getMessage()]);
        }
    }

    public function showResetPassword(): View
    {
        return view('auth.reset-password');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        // ===== Step 1: request a reset code (NIN + email) =====
        if ((int) $request->input('step') !== 2) {
            $request->validate([
                'nin' => ['required', 'string'],
                'email' => ['required', 'email'],
            ]);

            $user = User::where('nin', $request->nin)
                ->where('email', $request->email)
                ->first();

            // Enumeration-safe: only send when the account exists, but ALWAYS
            // advance to step 2 with the same neutral message either way, so the
            // response never reveals whether the NIN/email pair is registered.
            if ($user) {
                $this->issueOtp($user, 'reset', self::OTP_RESET_TTL_MINUTES);
            }

            return back()->with([
                'reset_step' => 2,
                'reset_nin' => $request->nin,
                'reset_email' => $request->email,
                'status' => __('auth.reset_otp_sent'),
            ]);
        }

        // Step 2 — the form carries NIN + email forward; re-derive the user from
        // them rather than trusting a client-supplied id.
        $user = User::where('nin', $request->nin)
            ->where('email', $request->email)
            ->first();

        // ===== Step 2 — resend branch (throttled by a cooldown) =====
        if ($request->boolean('resend')) {
            if ($user) {
                $cooldownKey = "otp_resend_cooldown_reset_{$user->id}";
                if (! Cache::has($cooldownKey)) {
                    $this->issueOtp($user, 'reset', self::OTP_RESET_TTL_MINUTES);
                    Cache::put($cooldownKey, true, now()->addSeconds(self::OTP_RESEND_COOLDOWN));
                }
            }

            return back()->with([
                'reset_step' => 2,
                'reset_nin' => $request->nin,
                'reset_email' => $request->email,
                'status' => __('auth.otp_resent'),
            ]);
        }

        // ===== Step 2 — verify code + set the new password =====
        $request->validate([
            'nin' => ['required', 'string'],
            'email' => ['required', 'email'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        // Any failure keeps the user on step 2 with NIN/email preserved.
        $backToStep2 = fn () => back()
            ->withInput($request->except('password', 'password_confirmation'))
            ->with(['reset_step' => 2, 'reset_nin' => $request->nin, 'reset_email' => $request->email]);

        $attemptsKey = $user ? "otp_attempts_reset_{$user->id}" : null;

        if ($user && (int) Cache::get($attemptsKey, 0) >= self::OTP_MAX_ATTEMPTS) {
            return $backToStep2()->withErrors(['otp' => __('auth.otp_too_many_attempts')]);
        }

        $cachedOtp = $user ? Cache::get("otp_reset_{$user->id}") : null;

        if (! $user || ! $cachedOtp || ! hash_equals($cachedOtp, $request->otp)) {
            if ($user) {
                Cache::put($attemptsKey, (int) Cache::get($attemptsKey, 0) + 1, now()->addMinutes(15));
            }

            return $backToStep2()->withErrors(['otp' => __('auth.otp_invalid')]);
        }

        $user->update(['password' => $request->password]);

        // Invalidate any existing sessions so a compromised session can't
        // survive a password reset (spec §8.4).
        invalidate_user_sessions($user->id);

        Cache::forget("otp_reset_{$user->id}");
        Cache::forget($attemptsKey);

        AuditLog::log('PASSWORD_RESET', 'User', $user->id);

        return redirect()->route('login')->with('success', __('auth.password_changed'));
    }

    public function showTwoFactorSetup(): View
    {
        return view('auth.two-factor-required');
    }

    public function showRecoverBySecret(): View
    {
        return view('auth.recover-secret');
    }

    /**
     * Account recovery via the user's secret question (spec §8.4 option 3 —
     * the fallback when email access is lost). Two steps: (1) NIN + email reveal
     * the stored question, (2) a correct answer lets the user set a new password.
     * Throttled at the route. The biometric/manual-review escalation is deferred.
     */
    public function recoverBySecret(Request $request): RedirectResponse
    {
        // ===== Step 1: identify the account and surface its question =====
        if ((int) $request->input('step') !== 2) {
            $request->validate([
                'nin' => ['required', 'string'],
                'email' => ['required', 'email'],
            ]);

            $user = User::where('nin', $request->nin)
                ->where('email', $request->email)
                ->whereNotNull('secret_question')
                ->whereNotNull('secret_answer')
                ->first();

            if (! $user) {
                return back()
                    ->withInput($request->only('nin', 'email'))
                    ->withErrors(['nin' => __('auth.recover_not_available')]);
            }

            return back()->with([
                'recover_step' => 2,
                'recover_nin' => $request->nin,
                'recover_email' => $request->email,
                'recover_question' => $user->secret_question,
            ]);
        }

        // ===== Step 2: verify the answer and set a new password =====
        $request->validate([
            'nin' => ['required', 'string'],
            'email' => ['required', 'email'],
            'secret_answer' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $user = User::where('nin', $request->nin)
            ->where('email', $request->email)
            ->whereNotNull('secret_answer')
            ->first();

        if (! $user || ! Hash::check($request->secret_answer, $user->secret_answer)) {
            return back()
                ->withInput($request->only('nin', 'email'))
                ->with([
                    'recover_step' => 2,
                    'recover_nin' => $request->nin,
                    'recover_email' => $request->email,
                    'recover_question' => $user?->secret_question ?? __('auth.recover_question_fallback'),
                ])
                ->withErrors(['secret_answer' => __('auth.recover_wrong_answer')]);
        }

        $user->update(['password' => $request->password]);
        invalidate_user_sessions($user->id);

        AuditLog::log('PASSWORD_RECOVERED_SECRET', 'User', $user->id);

        return redirect()->route('login')->with('success', __('auth.password_changed'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $userId = optional($request->user())->id;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($userId) {
            AuditLog::log('LOGOUT', 'User', $userId, $userId);
        }

        return redirect()->route('home');
    }
}
