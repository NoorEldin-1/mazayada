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

    // Secret key for generating deterministic bidder aliases (Section 6.5).
    // Falls back to APP_KEY when not explicitly set.
    'alias_secret' => env('ALIAS_SECRET', env('APP_KEY')),

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

        // Per-file upload caps (KB). Spec §3.2: identity scans ≤ 1MB,
        // biometric photo ≤ 120KB. Overridable via the system_settings table.
        'doc_max_kb' => 1024,
        'biometric_max_kb' => 120,

        'liveness_threshold' => (float) env('KYC_LIVENESS_THRESHOLD', 0.85),
        'match_threshold' => (float) env('KYC_MATCH_THRESHOLD', 0.80),
    ],

    'identity' => [
        // Best-effort NIN control-key validation (spec §3.1). OFF by default —
        // the reverse-engineered checksum is unconfirmed, so format-only is the
        // safe default. Flip on once verified against a civil-registry source.
        'nin_checksum_enforced' => (bool) env('NIN_CHECKSUM_ENFORCED', false),
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

        // How many days before the final-payment deadline the winner is reminded.
        'reminder_days_before_deadline' => 1,
    ],

    /*
    | Judicial-officer fee schedule (Decree 97-33) + TVA — spec §2.2.
    | All money is in integer centimes. Tiers are PROGRESSIVE (marginal): each
    | rate applies only to the portion of the value within that tier, like income
    | tax brackets. Scalar rates are mirrored into system_settings so a Super
    | Admin can tune them; the tier arrays live here (they don't fit the flat
    | key/value settings model).
    */
    'fees' => [
        // Appraisal / expertise fee — applies to all asset classes.
        'appraisal_tiers' => [
            ['upTo' => 3_000_000,  'rate' => 0.02],   // first 30,000 DZD
            ['upTo' => 10_000_000, 'rate' => 0.01],   // 30,000 → 100,000 DZD
            ['upTo' => null,       'rate' => 0.005],  // above 100,000 DZD
        ],

        // Hammer (adjudication) fee — MOVABLES only. Real estate uses the
        // proportional rights instead (no hammer-fee tier).
        'hammer_tiers' => [
            ['upTo' => 6_000_000,  'rate' => 0.06],   // up to 60,000 DZD
            ['upTo' => 20_000_000, 'rate' => 0.03],   // 60,000 → 200,000 DZD
            ['upTo' => null,       'rate' => 0.015],  // above 200,000 DZD
        ],

        'proportional_seller' => 0.05,                 // 5% — informational on the buyer receipt
        'proportional_buyer'  => 0.03,                 // 3% — borne by the buyer
        'work_session_flat_centimes' => 100_000,       // 1,000 DZD flat per 3-hour session
        'tva_rate' => 0.19,                            // VAT — applied to the fee subtotal
        'customs_min_immediate_rate' => 0.20,          // §2.3 — 20% due immediately on a customs win
        'newspaper_announcement_threshold_centimes' => 20_000_000, // §2.4 — >200,000 DZD needs press notice
    ],

    // Lease auction defaults (spec §2.4) — configurable per auction.
    'lease' => [
        'default_duration_years' => 3,
        'max_renewals' => 2,
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

        // Private disk for generated PDFs (award / condition book / receipt /
        // delivery report). Served only through gated routes — never the public
        // /storage symlink. See config/filesystems.php.
        'disk' => env('DOCUMENTS_DISK', 'documents'),

        // HMAC key used to sign the QR payload embedded in every document
        // (simplified electronic verification per Law 15-04; a real ANCE/X.509
        // certificate is a later phase). Falls back to APP_KEY.
        'signing_key' => env('DOCUMENT_SIGNING_KEY', env('APP_KEY')),
    ],

];
