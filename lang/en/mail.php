<?php

return [
    // ===== OTP verification email (registration) =====
    'otp' => [
        'subject' => 'Your email verification code — Mazayada',
        'greeting' => 'Hello :name,',
        'intro' => 'Use the following code to confirm your email address and finish creating your Mazayada account:',
        'code_label' => 'Verification code',
        'expiry' => 'This code is valid for :minutes minutes only.',
        'ignore' => 'If you did not request this code, you can safely ignore this message.',
        'footer' => 'This is an automated message from Mazayada, please do not reply.',
    ],

    // ===== OTP email (password reset) =====
    'reset' => [
        'subject' => 'Your password reset code — Mazayada',
        'greeting' => 'Hello :name,',
        'intro' => 'Use the following code to reset the password for your Mazayada account:',
        'code_label' => 'Verification code',
        'expiry' => 'This code is valid for :minutes minutes only.',
        'ignore' => 'If you did not request a password reset, you can safely ignore this message.',
        'footer' => 'This is an automated message from Mazayada, please do not reply.',
    ],

    // ===== KYC decision emails =====
    'kyc_approved' => [
        'subject' => 'Your identity is verified — Mazayada',
        'greeting' => 'Hello :name,',
        'intro' => 'We are pleased to inform you that your identity verification request has been approved. You can now take part in auctions on Mazayada.',
        'cta' => 'Go to my account',
        'footer' => 'This is an automated message from Mazayada, please do not reply.',
    ],
    'kyc_rejected' => [
        'subject' => 'Identity verification request rejected — Mazayada',
        'greeting' => 'Hello :name,',
        'intro' => 'We regret to inform you that your identity verification request has been rejected. You can correct your details and resubmit the request.',
        'reason_label' => 'Reason for rejection',
        'cta' => 'Correct the request',
        'footer' => 'This is an automated message from Mazayada, please do not reply.',
    ],
    'kyc_suspended' => [
        'subject' => 'Your account has been suspended — Mazayada',
        'greeting' => 'Hello :name,',
        'intro' => 'Your account has been suspended because identity verification was not completed within the allowed period. Complete it to reactivate your account.',
        'cta' => 'Complete verification',
        'footer' => 'This is an automated message from Mazayada, please do not reply.',
    ],
];
