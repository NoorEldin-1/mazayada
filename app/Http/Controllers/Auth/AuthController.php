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
            'nin_or_email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($request->nin_or_email, FILTER_VALIDATE_EMAIL) ? 'email' : 'nin';
        $user = User::where($field, $request->nin_or_email)->first();

        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            return back()->withErrors([
                'nin_or_email' => 'الحساب مقفل. حاول مجدداً بعد ' . $user->locked_until->diffForHumans(),
            ]);
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            if ($user) {
                $attempts = ($user->failed_login_attempts ?? 0) + 1;
                $update = ['failed_login_attempts' => $attempts];

                if ($attempts >= 5) {
                    $update['locked_until'] = now()->addMinutes(15);
                    $update['failed_login_attempts'] = 0;
                }

                $user->update($update);
            }

            AuditLog::log('LOGIN_FAILED', 'User', $user?->id ?? 'unknown', null, null, [
                'input' => $request->nin_or_email,
            ]);

            return back()->withErrors(['nin_or_email' => 'بيانات الدخول غير صحيحة.']);
        }

        $user->update(['failed_login_attempts' => 0, 'locked_until' => null]);
        Auth::login($user, $request->boolean('remember'));

        AuditLog::log('LOGIN_SUCCESS', 'User', $user->id);

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('citizen.dashboard');
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'nin' => ['required', 'string', new NinValidation, 'unique:users,nin'],
            'first_name_ar' => ['required', 'string', 'max:100'],
            'last_name_ar' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', new AlgerianPhone, 'unique:users,phone'],
            'email' => ['required', 'email', 'unique:users,email'],
            'birth_date' => ['required', 'date', 'before:' . now()->subYears(18)->toDateString()],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
        ]);

        $user = User::create([
            'nin' => $request->nin,
            'first_name_ar' => $request->first_name_ar,
            'last_name_ar' => $request->last_name_ar,
            'phone' => $request->phone,
            'email' => $request->email,
            'birth_date' => $request->birth_date,
            'password' => $request->password,
            'role' => UserRole::CITIZEN,
        ]);

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
            return back()->withErrors(['otp' => 'رمز التحقق غير صحيح أو منتهي الصلاحية.']);
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
                return back()->withErrors(['nin' => 'لم يتم العثور على الحساب.']);
            }

            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            Cache::put("reset_otp_{$user->id}", $otp, now()->addMinutes(10));

            // TODO: Send OTP via SMS/Email

            return back()->with([
                'step' => 2,
                'user_id' => $user->id,
                'message' => 'تم إرسال رمز التحقق.',
            ]);
        }

        // Step 2: Verify OTP and reset password
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
        ]);

        $cachedOtp = Cache::get("reset_otp_{$request->user_id}");

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return back()->withErrors(['otp' => 'رمز التحقق غير صحيح أو منتهي الصلاحية.']);
        }

        $user = User::findOrFail($request->user_id);
        $user->update(['password' => $request->password]);

        Cache::forget("reset_otp_{$user->id}");

        AuditLog::log('PASSWORD_RESET', 'User', $user->id);

        return redirect()->route('login')->with('success', 'تم تغيير كلمة المرور بنجاح.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
