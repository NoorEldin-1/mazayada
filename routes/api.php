<?php

use App\Http\Controllers\Api\V1\AppealController;
use App\Http\Controllers\Api\V1\AuctionController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BidController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\KycController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\QuestionController;
use App\Http\Controllers\Api\V1\RegistrationController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
| Reverb broadcasting authorization for the mobile client. Publishes
| POST /api/broadcasting/auth guarded by the Sanctum access token, so the Flutter
| app can authorize PRIVATE channels (e.g. auction.{id}.user.{id}) with its bearer
| token. The web /broadcasting/auth (session-guarded) is untouched and coexists.
| Channel rules live in routes/channels.php and are guard-agnostic.
*/
Broadcast::routes(['middleware' => ['auth:sanctum', 'ability:access', 'active.account']]);

/*
|--------------------------------------------------------------------------
| Mobile API Routes (versioned)
|--------------------------------------------------------------------------
|
| Token-authenticated JSON API consumed by the Flutter client. Registered in
| bootstrap/app.php with the `api` prefix, so every route here lives under
| `/api/...`. The framework `api` middleware group (throttle:api,
| SubstituteBindings) plus our ApiSetLocale + ForceJsonResponse run on all of
| them. Versioned via the `v1` prefix; a future `v2` group can be added below
| without touching v1 (shared logic lives in services/actions).
|
| NOTE: the public geo reference endpoints (`/api/v1/wilayas[/communes]`) are
| intentionally still defined in routes/web.php — the web Blade forms consume
| their bare-array shape, and the Flutter client uses the same path.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {

    // --- System / health -------------------------------------------------
    Route::get('/ping', [HealthController::class, 'ping'])->name('ping');

    // --- Authentication --------------------------------------------------
    Route::prefix('auth')->name('auth.')->group(function (): void {
        // Public (guest) endpoints — extra-strict throttling on top of throttle:api.
        Route::middleware('throttle:api-auth')->group(function (): void {
            Route::post('/register', [AuthController::class, 'register'])->name('register');
            Route::post('/login', [AuthController::class, 'login'])->name('login');
            Route::post('/password/request', [AuthController::class, 'requestPasswordReset'])->name('password.request');
            Route::post('/password/verify', [AuthController::class, 'verifyPasswordReset'])->name('password.verify');
            Route::post('/recover/reveal', [AuthController::class, 'revealSecretQuestion'])->name('recover.reveal');
            Route::post('/recover/verify', [AuthController::class, 'recoverBySecret'])->name('recover.verify');
        });

        // OTP endpoints — the strictest limiter (per IP + identifier).
        Route::middleware('throttle:api-otp')->group(function (): void {
            Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('verify-otp');
            Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('resend-otp');
        });

        // Refresh — the bearer here is the REFRESH token (ability:refresh).
        Route::post('/refresh', [AuthController::class, 'refresh'])
            ->middleware(['auth:sanctum', 'ability:refresh', 'active.account'])
            ->name('refresh');

        // Access-token protected.
        Route::middleware(['auth:sanctum', 'ability:access', 'active.account'])->group(function (): void {
            Route::get('/me', [AuthController::class, 'me'])->name('me');
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        });
    });

    // --- Auctions (public, read-only) -----------------------------------
    Route::prefix('auctions')->name('auctions.')->group(function (): void {
        Route::get('/', [AuctionController::class, 'index'])->name('index');
        Route::get('/{auction}', [AuctionController::class, 'show'])->name('show');
        Route::get('/{auction}/bids', [AuctionController::class, 'latestBids'])->name('bids');
        Route::get('/{auction}/price', [AuctionController::class, 'price'])->name('price');
        Route::get('/{auction}/questions', [AuctionController::class, 'questions'])->name('questions');
    });

    // --- Documents (authenticated) --------------------------------------
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
        ->middleware(['auth:sanctum', 'ability:access', 'active.account'])
        ->name('documents.download');

    // --- Payment gateway return (public, idempotent) --------------------
    Route::get('/payments/callback', [PaymentController::class, 'callback'])->name('payments.callback');

    // --- Authenticated citizen area -------------------------------------
    Route::middleware(['auth:sanctum', 'ability:access', 'active.account'])->group(function (): void {
        // Poll the result of a checkout after the gateway web view returns.
        Route::get('/payments/{ref}/status', [PaymentController::class, 'status'])->name('payments.status');

        // Dashboard + participation history.
        Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');
        Route::get('/my-auctions', [DashboardController::class, 'myAuctions'])->name('my-auctions');

        // Profile.
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        // KYC (NOT kyc-gated — these are how an unverified user gets verified).
        Route::prefix('kyc')->name('kyc.')->group(function (): void {
            Route::get('/', [KycController::class, 'show'])->name('show');
            Route::post('/upload/{type}', [KycController::class, 'upload'])->name('upload');
            Route::post('/submit', [KycController::class, 'submit'])->name('submit');
            Route::get('/document/{type}', [KycController::class, 'document'])->name('document');
        });

        // Appeals — list mine, and file one against a closed auction I took part in.
        Route::get('/appeals', [AppealController::class, 'index'])->name('appeals.index');
        Route::post('/auctions/{auction}/appeals', [AppealController::class, 'store'])->name('appeals.store');

        // Notifications.
        Route::prefix('notifications')->name('notifications.')->group(function (): void {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
            Route::post('/{notification}/read', [NotificationController::class, 'markRead'])->name('read');
            Route::post('/read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
        });

        // KYC-gated actions (parity with the web 'kyc.verified' group).
        Route::middleware('api.kyc')->group(function (): void {
            Route::post('/auctions/{auction}/buy-book', [RegistrationController::class, 'buyConditionBook'])->name('auctions.buy-book');
            Route::post('/auctions/{auction}/register', [RegistrationController::class, 'startRegistration'])->name('auctions.register');
            Route::post('/auctions/{auction}/final-payment', [PaymentController::class, 'startFinalPayment'])->name('auctions.final-payment');
            Route::post('/auctions/{auction}/questions', [QuestionController::class, 'store'])->name('auctions.questions.store');
            Route::post('/auctions/{auction}/bid', [BidController::class, 'store'])
                ->middleware('throttle:bidding')
                ->name('auctions.bid');
        });
    });

    // Subsequent phases register their route groups here:
    //   - dashboard, profile, kyc, appeals, notifications
    //   - broadcasting auth (Reverb) for private channels
});
