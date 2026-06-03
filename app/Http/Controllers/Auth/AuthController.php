<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Rules\AlgerianPhone;
use App\Rules\NinValidation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
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

        RateLimiter::clear($throttleKey);
        $user->update(['failed_login_attempts' => 0, 'locked_until' => null]);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        AuditLog::log('LOGIN_SUCCESS', 'User', $user->id, $user->id, $user->role?->value, [
            'ip' => $request->ip(),
            'ua' => substr((string) $request->userAgent(), 0, 200),
        ]);

        if ($user->isAdmin()) {
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

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put("otp_{$user->id}", $otp, now()->addMinutes(5));

        // TODO: Send OTP via SMS/Email

        return redirect()->route('verify-otp')->with('user_id', $user->id);
    }

    public function showVerifyOtp(): View
    {
        return view('auth.verify-otp');
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $cachedOtp = Cache::get("otp_{$request->user_id}");

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return back()->withErrors(['otp' => __('auth.otp_invalid')]);
        }

        $user = User::findOrFail($request->user_id);
        $user->update([
            'phone_verified' => true,
            'email_verified' => true,
        ]);

        Cache::forget("otp_{$user->id}");
        Auth::login($user);

        AuditLog::log('OTP_VERIFIED', 'User', $user->id);

        return redirect()->route('citizen.dashboard');
    }

    public function showResetPassword(): View
    {
        return view('auth.reset-password');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        // Step 1: Generate OTP
        if (!$request->has('otp')) {
            $request->validate([
                'nin' => ['required', 'string'],
                'email' => ['required', 'email'],
            ]);

            $user = User::where('nin', $request->nin)
                ->where('email', $request->email)
                ->first();

            if (!$user) {
                return back()->withErrors(['nin' => __('auth.account_not_found')]);
            }

            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            Cache::put("reset_otp_{$user->id}", $otp, now()->addMinutes(10));

            // TODO: Send OTP via SMS/Email

            return back()->with([
                'step' => 2,
                'user_id' => $user->id,
                'message' => __('auth.otp_sent'),
            ]);
        }

        // Step 2: Verify OTP and reset password
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $cachedOtp = Cache::get("reset_otp_{$request->user_id}");

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return back()->withErrors(['otp' => __('auth.otp_invalid')]);
        }

        $user = User::findOrFail($request->user_id);
        $user->update(['password' => $request->password]);

        Cache::forget("reset_otp_{$user->id}");

        AuditLog::log('PASSWORD_RESET', 'User', $user->id);

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
