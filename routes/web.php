<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\AuctionController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ChargilyWebhookController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Citizen\CitizenController;
use App\Http\Controllers\Citizen\KycController;
use App\Http\Controllers\Citizen\CommercialRegisterController;
use App\Http\Controllers\Citizen\AppealController;
use App\Http\Controllers\Citizen\ReportController;
use App\Http\Controllers\Citizen\DocumentLibraryController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminAuctionController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminKycController;
use App\Http\Controllers\Admin\AdminCommercialRegisterController;
use App\Http\Controllers\Admin\AdminAppealController;
use App\Http\Controllers\Admin\AdminEntityController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminEntityStaffController;
use App\Http\Controllers\Admin\AdminInspectionController;
use App\Http\Controllers\Admin\AdminDeliveryController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AuctionReportController;
use App\Http\Controllers\Api\GeoController;

// Public
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/auctions', [AuctionController::class, 'index'])->name('auctions.index');
// Live-search JSON endpoint — MUST precede the /auctions/{auction} wildcard, or
// "search" would be treated as an auction id and 404 on route-model binding.
Route::get('/auctions/search', [AuctionController::class, 'search'])->name('auctions.search');
Route::get('/auctions/{auction}', [AuctionController::class, 'show'])->name('auctions.show');
Route::get('/how-it-works', [PageController::class, 'howItWorks'])->name('how-it-works');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/appeals-guide', [PageController::class, 'appealsGuide'])->name('appeals.guide');
Route::get('/identity-guide', [PageController::class, 'identityGuide'])->name('identity.guide');

// Public document verification via the QR code on every generated PDF (spec §9.3).
Route::get('/verify', [DocumentController::class, 'verify'])->name('documents.verify');

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
    // Brute-force protection lives inside AuthController::login (graceful on-form
    // message + progressive per-account lock, and it never throttles staff). A
    // route-level throttle:login would return a raw 429 page and can't tell
    // citizens from staff apart — so it's intentionally omitted here.
    Route::post('/login', [AuthController::class, 'login']);
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
    // Commercial Register (السجل التجاري) — user submits data + scans, admin reviews.
    Route::get('/commercial-register', [CommercialRegisterController::class, 'index'])->name('commercial-register');
    Route::post('/commercial-register', [CommercialRegisterController::class, 'store'])->name('commercial-register.store');
    Route::get('/commercial-register/document/{type}', [CommercialRegisterController::class, 'document'])->name('commercial-register.document');
    // Appeals are now filed from the auction page; this stays as the tracking list.
    Route::get('/appeals', [AppealController::class, 'index'])->name('appeals');
    Route::get('/my-auctions', [CitizenController::class, 'myAuctions'])->name('my-auctions');
    // Personal document library (الوثائق) — searchable archive of the user's
    // auction paperwork (condition books, award / receipt / delivery documents).
    Route::get('/documents', [DocumentLibraryController::class, 'index'])->name('documents');
    // Personal financial report — the citizen's own payments, filtered + analysed.
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
    Route::get('/notifications', [CitizenController::class, 'notifications'])->name('notifications');
    Route::post('/notifications/{notification}/read', [CitizenController::class, 'markNotificationRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [CitizenController::class, 'markAllNotificationsRead'])->name('notifications.read-all');
    Route::get('/profile', [CitizenController::class, 'profile'])->name('profile');
    Route::put('/profile', [CitizenController::class, 'updateProfile'])->name('profile.update');
});

// Chargily Pay server-to-server webhook — the AUTHORITATIVE payment confirmation
// (the browser return URL above is UX only). Public + CSRF-exempt (see
// bootstrap/app.php); the controller verifies Chargily's HMAC signature.
Route::post('/payments/chargily/webhook', [ChargilyWebhookController::class, 'handle'])
    ->name('payments.chargily.webhook');

// Authenticated, non-KYC-gated document + payment-callback routes.
Route::middleware('auth')->group(function () {
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    // Payment gateway return URL (mock + CIBWeb) for every checkout flow.
    Route::get('/payments/callback', [AuctionController::class, 'paymentCallback'])->name('payments.callback');
});

// Auction actions (authenticated) — bidding is rate-limited per user per auction.
Route::middleware(['auth', 'kyc.verified'])->group(function () {
    // §4 step 2 — buy the condition book (دفتر الشروط); a prerequisite for
    // registering, but also open to any KYC-verified user who just wants to read it.
    Route::post('/auctions/{auction}/buy-book', [AuctionController::class, 'buyConditionBook'])->name('auctions.buy-book');
    // §4 step 3 — paid registration (participation deposit).
    Route::post('/auctions/{auction}/register', [AuctionController::class, 'startRegistration'])->name('auctions.register');
    // §4 step 7 — the winner's final payment.
    Route::post('/auctions/{auction}/final-payment', [AuctionController::class, 'startFinalPayment'])->name('auctions.final-payment');
    // §4 step 4 — bidder inspection question.
    Route::post('/auctions/{auction}/questions', [AuctionController::class, 'askQuestion'])->name('auctions.questions');
    Route::post('/auctions/{auction}/bid', [AuctionController::class, 'bid'])->middleware('throttle:bidding')->name('auctions.bid');
    // § الطعون — file an appeal against a closed auction the user took part in.
    Route::post('/auctions/{auction}/appeals', [AppealController::class, 'store'])->name('auctions.appeals.store');
});

// Admin — open to every staff role; per-action access is enforced by policies
// (AuthorizesRequests / $this->authorize) and the Spatie permission gate.
// admin.2fa redirects staff without a confirmed 2FA to the setup page when the
// 'security.enforce_admin_2fa' setting is on (off by default).
Route::middleware(['auth', 'admin.2fa', 'role:'.implode(',', \App\Enums\UserRole::staffValues())])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/auctions', [AdminAuctionController::class, 'index'])->name('auctions.index');
    Route::get('/auctions/create', [AdminAuctionController::class, 'create'])->name('auctions.create');
    Route::post('/auctions', [AdminAuctionController::class, 'store'])->name('auctions.store');
    // Read-only full detail (registered after /create so it isn't shadowed).
    Route::get('/auctions/{auction}', [AdminAuctionController::class, 'show'])->name('auctions.show');
    Route::get('/auctions/{auction}/edit', [AdminAuctionController::class, 'edit'])->name('auctions.edit');
    Route::put('/auctions/{auction}', [AdminAuctionController::class, 'update'])->name('auctions.update');
    Route::delete('/auctions/{auction}', [AdminAuctionController::class, 'destroy'])->name('auctions.destroy');
    Route::post('/auctions/{auction}/publish', [AdminAuctionController::class, 'publish'])->name('auctions.publish');
    Route::post('/auctions/{auction}/start', [AdminAuctionController::class, 'start'])->name('auctions.start');
    Route::post('/auctions/{auction}/extend', [AdminAuctionController::class, 'extend'])->name('auctions.extend');
    Route::post('/auctions/{auction}/cancel', [AdminAuctionController::class, 'cancel'])->name('auctions.cancel');
    // §4 step 2 — generate the signed condition book.
    Route::post('/auctions/{auction}/condition-book', [AdminAuctionController::class, 'publishConditionBook'])->name('auctions.condition-book');

    // §4 step 4 — inspection Q&A moderation.
    Route::get('/inspections', [AdminInspectionController::class, 'index'])->name('inspections.index');
    Route::post('/inspection-questions/{question}/answer', [AdminInspectionController::class, 'answer'])->name('inspections.answer');
    Route::post('/inspection-questions/{question}/reject', [AdminInspectionController::class, 'reject'])->name('inspections.reject');

    // §4 step 9 — delivery scheduling.
    Route::get('/deliveries', [AdminDeliveryController::class, 'index'])->name('deliveries.index');
    Route::post('/auctions/{auction}/delivery', [AdminDeliveryController::class, 'store'])->name('deliveries.store');
    Route::post('/deliveries/{delivery}/deliver', [AdminDeliveryController::class, 'markDelivered'])->name('deliveries.deliver');

    // Entity management (SUPER_ADMIN via entities.manage)
    Route::get('/entities', [AdminEntityController::class, 'index'])->name('entities.index');
    Route::get('/entities/create', [AdminEntityController::class, 'create'])->name('entities.create');
    Route::post('/entities', [AdminEntityController::class, 'store'])->name('entities.store');
    // Read-only full detail (registered after /create so it isn't shadowed).
    Route::get('/entities/{entity}', [AdminEntityController::class, 'show'])->name('entities.show');
    Route::get('/entities/{entity}/edit', [AdminEntityController::class, 'edit'])->name('entities.edit');
    Route::put('/entities/{entity}', [AdminEntityController::class, 'update'])->name('entities.update');
    Route::delete('/entities/{entity}', [AdminEntityController::class, 'destroy'])->name('entities.destroy');
    // Active staff of an entity (JSON) — feeds the cascading staff select on the auction form.
    Route::get('/entities/{entity}/staff', [AdminEntityStaffController::class, 'staff'])->name('entities.staff');

    // Entity-staff management (SUPER_ADMIN + ENTITY_HEAD via entities.members.manage)
    Route::get('/entity-staff', [AdminEntityStaffController::class, 'index'])->name('entity-staff.index');
    Route::get('/entity-staff/create', [AdminEntityStaffController::class, 'create'])->name('entity-staff.create');
    Route::post('/entity-staff', [AdminEntityStaffController::class, 'store'])->name('entity-staff.store');
    // Read-only full detail (registered after /create so it isn't shadowed).
    Route::get('/entity-staff/{entityStaff}', [AdminEntityStaffController::class, 'show'])->name('entity-staff.show');
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

    // Commercial Register (السجل التجاري) review queue.
    Route::get('/commercial-registers', [AdminCommercialRegisterController::class, 'pending'])->name('commercial-registers.index');
    Route::get('/commercial-registers/{commercialRegister}', [AdminCommercialRegisterController::class, 'show'])->name('commercial-registers.show');
    Route::get('/commercial-registers/{commercialRegister}/document/{type}', [AdminCommercialRegisterController::class, 'document'])->name('commercial-registers.document');
    Route::post('/commercial-registers/{commercialRegister}/approve', [AdminCommercialRegisterController::class, 'approve'])->name('commercial-registers.approve');
    Route::post('/commercial-registers/{commercialRegister}/reject', [AdminCommercialRegisterController::class, 'reject'])->name('commercial-registers.reject');
    Route::get('/appeals', [AdminAppealController::class, 'index'])->name('appeals.index');
    // Appeals workflow: admin forward / reject-at-intake / confirm; entity decide.
    Route::post('/appeals/{appeal}/forward', [AdminAppealController::class, 'forward'])->name('appeals.forward');
    Route::post('/appeals/{appeal}/reject', [AdminAppealController::class, 'rejectAtIntake'])->name('appeals.reject');
    Route::post('/appeals/{appeal}/decide', [AdminAppealController::class, 'decide'])->name('appeals.decide');
    Route::post('/appeals/{appeal}/confirm', [AdminAppealController::class, 'confirm'])->name('appeals.confirm');
    Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('audit-logs');

    // Financial reports (التقارير المالية) — platform-wide for SUPER_ADMIN, or
    // scoped to the account's own entity (EntityScope) for entity staff/viewers.
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/csv', [AdminReportController::class, 'exportCsv'])->name('reports.export.csv');
    Route::get('/reports/export/pdf', [AdminReportController::class, 'exportPdf'])->name('reports.export.pdf');

    // Auction reports (تقارير المزادات) — full per-auction detail snapshots.
    // Module list for admin (all) + entity (referred only); issue/view/refer.
    Route::get('/auction-reports', [AuctionReportController::class, 'index'])->name('auction-reports.index');
    Route::post('/auctions/{auction}/reports', [AuctionReportController::class, 'generate'])->name('auctions.reports.generate');
    Route::get('/auctions/{auction}/reports/latest', [AuctionReportController::class, 'latest'])->name('auctions.reports.latest');
    Route::get('/auction-reports/{report}/view', [AuctionReportController::class, 'view'])->name('auction-reports.view');
    Route::post('/auction-reports/{report}/refer', [AuctionReportController::class, 'refer'])->name('auction-reports.refer');
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
