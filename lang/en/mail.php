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
];
