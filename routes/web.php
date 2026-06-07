<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\AuctionController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Citizen\CitizenController;
use App\Http\Controllers\Citizen\KycController;
use App\Http\Controllers\Citizen\AppealController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminAuctionController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminKycController;
use App\Http\Controllers\Admin\AdminAppealController;
use App\Http\Controllers\Admin\AdminEntityController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminEntityStaffController;
use App\Http\Controllers\Api\GeoController;

// Public
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/auctions', [AuctionController::class, 'index'])->name('auctions.index');
Route::get('/auctions/{auction}', [AuctionController::class, 'show'])->name('auctions.show');
Route::get('/how-it-works', [PageController::class, 'howItWorks'])->name('how-it-works');
Route::get('/about', [PageController::class, 'about'])->name('about');

// Legal / static policy pages — linked from the footer and the registration form.
Route::prefix('legal')->name('legal.')->group(function () {
    Route::get('/terms', [PageController::class, 'terms'])->name('terms');
    Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
    Route::get('/framework', [PageController::class, 'framework'])->name('framework');
    Route::get('/notices', [PageController::class, 'notices'])->name('notices');
});

// Auth (guest only) — throttled to prevent brute force
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::get('/verify-otp', [AuthController::class, 'showVerifyOtp'])->name('verify-otp');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:two-factor');
    Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:3,1');
    // Account recovery via the secret question — the fallback when the user has
    // lost access to their email (spec §8.4 option 3, biometric step deferred).
    Route::get('/recover', [AuthController::class, 'showRecoverBySecret'])->name('password.recover');
    Route::post('/recover', [AuthController::class, 'recoverBySecret'])->middleware('throttle:3,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Two-factor setup landing — staff are sent here when admin 2FA enforcement is
// on and they have not yet confirmed a second factor.
Route::get('/two-factor/setup', [AuthController::class, 'showTwoFactorSetup'])
    ->middleware('auth')->name('two-factor.setup');

// Citizen (authenticated)
Route::middleware('auth')->prefix('dashboard')->name('citizen.')->group(function () {
    Route::get('/', [CitizenController::class, 'dashboard'])->name('dashboard');
    Route::get('/kyc', [KycController::class, 'index'])->name('kyc');
    Route::post('/kyc/upload/{type}', [KycController::class, 'upload'])->name('kyc.upload');
    Route::post('/kyc/submit', [KycController::class, 'submit'])->name('kyc.submit');
    Route::get('/kyc/document/{type}', [KycController::class, 'document'])->name('kyc.document');
    Route::get('/appeals', [AppealController::class, 'index'])->name('appeals');
    Route::post('/appeals', [AppealController::class, 'store'])->name('appeals.store');
    Route::get('/my-auctions', [CitizenController::class, 'myAuctions'])->name('my-auctions');
    Route::get('/notifications', [CitizenController::class, 'notifications'])->name('notifications');
    Route::get('/profile', [CitizenController::class, 'profile'])->name('profile');
    Route::put('/profile', [CitizenController::class, 'updateProfile'])->name('profile.update');
});

// Auction actions (authenticated) — bidding is rate-limited per user per auction.
Route::middleware(['auth', 'kyc.verified'])->group(function () {
    Route::post('/auctions/{auction}/register', [AuctionController::class, 'registerParticipant'])->name('auctions.register');
    Route::post('/auctions/{auction}/bid', [AuctionController::class, 'bid'])->middleware('throttle:bidding')->name('auctions.bid');
});

// Admin — open to every staff role; per-action access is enforced by policies
// (AuthorizesRequests / $this->authorize) and the Spatie permission gate.
// admin.2fa redirects staff without a confirmed 2FA to the setup page when the
// 'security.enforce_admin_2fa' setting is on (off by default).
Route::middleware(['auth', 'admin.2fa', 'role:SUPER_ADMIN,ENTITY_HEAD,CONTENT_ADMIN,APPRAISER,HUISSIER,COMMITTEE_MEMBER'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/auctions', [AdminAuctionController::class, 'index'])->name('auctions.index');
    Route::get('/auctions/create', [AdminAuctionController::class, 'create'])->name('auctions.create');
    Route::post('/auctions', [AdminAuctionController::class, 'store'])->name('auctions.store');
    Route::get('/auctions/{auction}/edit', [AdminAuctionController::class, 'edit'])->name('auctions.edit');
    Route::put('/auctions/{auction}', [AdminAuctionController::class, 'update'])->name('auctions.update');
    Route::delete('/auctions/{auction}', [AdminAuctionController::class, 'destroy'])->name('auctions.destroy');
    Route::post('/auctions/{auction}/publish', [AdminAuctionController::class, 'publish'])->name('auctions.publish');
    Route::post('/auctions/{auction}/start', [AdminAuctionController::class, 'start'])->name('auctions.start');
    Route::post('/auctions/{auction}/extend', [AdminAuctionController::class, 'extend'])->name('auctions.extend');
    Route::post('/auctions/{auction}/cancel', [AdminAuctionController::class, 'cancel'])->name('auctions.cancel');

    // Entity management (SUPER_ADMIN via entities.manage)
    Route::get('/entities', [AdminEntityController::class, 'index'])->name('entities.index');
    Route::get('/entities/create', [AdminEntityController::class, 'create'])->name('entities.create');
    Route::post('/entities', [AdminEntityController::class, 'store'])->name('entities.store');
    Route::get('/entities/{entity}/edit', [AdminEntityController::class, 'edit'])->name('entities.edit');
    Route::put('/entities/{entity}', [AdminEntityController::class, 'update'])->name('entities.update');
    Route::delete('/entities/{entity}', [AdminEntityController::class, 'destroy'])->name('entities.destroy');

    // Entity-staff management (SUPER_ADMIN + ENTITY_HEAD via entities.members.manage)
    Route::get('/entity-staff', [AdminEntityStaffController::class, 'index'])->name('entity-staff.index');
    Route::get('/entity-staff/create', [AdminEntityStaffController::class, 'create'])->name('entity-staff.create');
    Route::post('/entity-staff', [AdminEntityStaffController::class, 'store'])->name('entity-staff.store');
    Route::get('/entity-staff/{entityStaff}/edit', [AdminEntityStaffController::class, 'edit'])->name('entity-staff.edit');
    Route::put('/entity-staff/{entityStaff}', [AdminEntityStaffController::class, 'update'])->name('entity-staff.update');
    Route::post('/entity-staff/{entityStaff}/toggle', [AdminEntityStaffController::class, 'deactivate'])->name('entity-staff.toggle');

    // Category management (categories.manage)
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

    // System parameters (SUPER_ADMIN via system.parameters.manage)
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');

    // Users — blacklisted list before the {user} wildcard so it isn't captured.
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/blacklisted', [AdminUserController::class, 'blacklisted'])->name('users.blacklisted');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/blacklist', [AdminUserController::class, 'blacklist'])->name('users.blacklist');
    Route::post('/users/{user}/unblacklist', [AdminUserController::class, 'unblacklist'])->name('users.unblacklist');
    Route::get('/kyc', [AdminKycController::class, 'pending'])->name('kyc.index');
    Route::get('/kyc/{user}', [AdminKycController::class, 'show'])->name('kyc.show');
    Route::get('/kyc/{user}/document/{type}', [AdminKycController::class, 'document'])->name('kyc.document');
    Route::post('/kyc/{user}/approve', [AdminKycController::class, 'approve'])->name('kyc.approve');
    Route::post('/kyc/{user}/reject', [AdminKycController::class, 'reject'])->name('kyc.reject');
    Route::get('/appeals', [AdminAppealController::class, 'index'])->name('appeals.index');
    Route::post('/appeals/{appeal}/respond', [AdminAppealController::class, 'respond'])->name('appeals.respond');
    Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit-logs');
});

// API (public)
Route::prefix('api/v1')->group(function () {
    Route::get('/wilayas', [GeoController::class, 'wilayas']);
    Route::get('/wilayas/{wilaya}/communes', [GeoController::class, 'communes']);
});

// Set language — works for guests (session) and authenticated users (persisted
// to their account so the choice follows them across devices).
Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, config('locales.supported', ['ar']), true)) {
        session(['locale' => $locale]);

        if ($user = request()->user()) {
            $user->update(['locale' => $locale]);
        }
    }

    return back();
})->name('lang.switch');
