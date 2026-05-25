<?php

/*
|--------------------------------------------------------------------------
| Mazayada Platform Configuration
|--------------------------------------------------------------------------
|
| Central place for platform-wide tunables. Keeping these out of code means
| Super Admins (per Section 8.2 of the spec) can adjust runtime parameters
| without code changes — they can be made DB-backed in a later phase.
|
*/

return [

    'bidding' => [
        'max_per_minute' => (int) env('BID_MAX_PER_MINUTE', 10),

        // Default auto-extension parameters (Section 6.3).
        // These are baseline defaults — individual auctions may override.
        'extension_trigger_seconds' => 30,
        'extension_duration_minutes' => 5,
    ],

    'kyc' => [
        // After how many days a PENDING KYC results in account suspension.
        'pending_grace_days' => 30,

        'liveness_threshold' => (float) env('KYC_LIVENESS_THRESHOLD', 0.85),
        'match_threshold' => (float) env('KYC_MATCH_THRESHOLD', 0.80),
    ],

    'payments' => [
        // When true, use the in-process mock CIBWeb client (Phase 1 / dev / testing).
        'mock' => (bool) env('CIBWEB_MOCK', true),

        'cibweb' => [
            'base_url' => env('CIBWEB_BASE_URL'),
            'username' => env('CIBWEB_USERNAME'),
            'password' => env('CIBWEB_PASSWORD'),
            'currency' => '012', // DZD per CIBWeb spec
        ],

        // Final payment deadlines per Algerian CPC Art. 373.
        'final_payment_deadline_days' => [
            'movable' => 8,
            'real_estate' => 15,
        ],
    ],

    'security' => [
        'login_max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'login_decay_minutes' => (int) env('LOGIN_DECAY_MINUTES', 15),
    ],

    'cache' => [
        'wilayas_ttl_minutes' => 60 * 24, // 24 hours — quasi-static reference data
        'communes_ttl_minutes' => 60 * 24,
        'categories_ttl_minutes' => 60 * 6, // 6 hours
        'active_auctions_ttl_seconds' => 30, // very short — live data
    ],

    'documents' => [
        'qr_verification_base_url' => env('QR_VERIFICATION_BASE_URL'),
    ],

];
