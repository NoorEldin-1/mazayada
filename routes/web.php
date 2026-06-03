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
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Citizen (authenticated)
Route::middleware('auth')->prefix('dashboard')->name('citizen.')->group(function () {
    Route::get('/', [CitizenController::class, 'dashboard'])->name('dashboard');
    Route::get('/kyc', [KycController::class, 'index'])->name('kyc');
    Route::post('/kyc/upload/{type}', [KycController::class, 'upload'])->name('kyc.upload');
    Route::post('/kyc/submit', [KycController::class, 'submit'])->name('kyc.submit');
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

// Admin
Route::middleware(['auth', 'role:SUPER_ADMIN,ENTITY_HEAD,CONTENT_ADMIN'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/auctions', [AdminAuctionController::class, 'index'])->name('auctions.index');
    Route::get('/auctions/create', [AdminAuctionController::class, 'create'])->name('auctions.create');
    Route::post('/auctions', [AdminAuctionController::class, 'store'])->name('auctions.store');
    Route::get('/auctions/{auction}/edit', [AdminAuctionController::class, 'edit'])->name('auctions.edit');
    Route::put('/auctions/{auction}', [AdminAuctionController::class, 'update'])->name('auctions.update');
    Route::delete('/auctions/{auction}', [AdminAuctionController::class, 'destroy'])->name('auctions.destroy');
    Route::post('/auctions/{auction}/publish', [AdminAuctionController::class, 'publish'])->name('auctions.publish');
    Route::post('/auctions/{auction}/start', [AdminAuctionController::class, 'start'])->name('auctions.start');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/blacklist', [AdminUserController::class, 'blacklist'])->name('users.blacklist');
    Route::get('/kyc', [AdminKycController::class, 'pending'])->name('kyc.index');
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
