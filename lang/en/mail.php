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

    // ===== Commercial Register decision emails =====
    'cr_approved' => [
        'subject' => 'Your commercial register is approved — Mazayada',
        'greeting' => 'Hello :name,',
        'intro' => 'We are pleased to inform you that your commercial register has been approved. You can now participate in auctions that require a commercial register.',
        'cta' => 'View commercial register',
        'footer' => 'This is an automated message from Mazayada, please do not reply.',
    ],
    'cr_rejected' => [
        'subject' => 'Your commercial register has been rejected — Mazayada',
        'greeting' => 'Hello :name,',
        'intro' => 'We regret to inform you that your commercial register has been rejected. You can correct the details and resubmit your request.',
        'reason_label' => 'Reason for rejection',
        'cta' => 'Correct the request',
        'footer' => 'This is an automated message from Mazayada, please do not reply.',
    ],

    // ===== Auction lifecycle event emails (spec §10.1) =====
    'events_common' => [
        'greeting' => 'Hello :name,',
        'cta' => 'View details',
        'footer' => 'This is an automated message from Mazayada, please do not reply.',
    ],
    'events' => [
        'auction_won' => [
            'subject' => 'Congratulations! You won the auction — Mazayada',
            'line' => 'You won the auction “:auction” for :amount. The final payment must be made within :days days.',
        ],
        'auction_lost' => [
            'subject' => 'Auction ended — Mazayada',
            'line' => 'The auction “:auction” has ended and you were not the highest bidder. Your deposit will be refunded automatically.',
        ],
        'payment_confirmed' => [
            'subject' => 'Payment confirmed — Mazayada',
            'line' => 'Your :type payment of :amount for the auction “:auction” has been confirmed.',
        ],
        'payment_failed' => [
            'subject' => 'Payment failed — Mazayada',
            'line' => 'Your :type payment for the auction “:auction” did not go through. You can try again.',
        ],
        'final_payment_due' => [
            'subject' => 'Final payment due — Mazayada',
            'line' => 'The final payment for the auction “:auction” must be made within :days days, otherwise the deposit will be forfeited.',
        ],
        'deposit_refunded' => [
            'subject' => 'Deposit refunded — Mazayada',
            'line' => 'Your deposit of :amount for the auction “:auction” has been refunded.',
        ],
        'deposit_forfeited' => [
            'subject' => 'Deposit forfeited — Mazayada',
            'line' => 'Because the final payment for the auction “:auction” was not made in time, your deposit was forfeited and your account blacklisted.',
        ],
        'outbid' => [
            'subject' => 'You have been outbid — Mazayada',
            'line' => 'A higher bid of :amount was placed on the auction “:auction”. Place a new bid soon.',
        ],
        'inspection_answered' => [
            'subject' => 'Your question was answered — Mazayada',
            'line' => 'Your question about the auction “:auction” has been answered.',
        ],
        'condition_book_published' => [
            'subject' => 'Condition book published — Mazayada',
            'line' => 'The condition book for the auction “:auction” is now available. Review it before registering.',
        ],
        'delivery_update' => [
            'subject' => 'Delivery update — Mazayada',
            'line' => 'The delivery status for the auction “:auction” changed to: :status.',
        ],
        'appeal_updated' => [
            'subject' => 'Update on your appeal — Mazayada',
            'line' => 'Your appeal status changed to: :status.',
        ],
        'appeal_submitted' => [
            'subject' => 'New appeal — Mazayada',
            'line' => 'A new appeal has been filed on the auction “:auction” and awaits review.',
        ],
        'appeal_forwarded' => [
            'subject' => 'An appeal forwarded to you — Mazayada',
            'line' => 'An appeal on the auction “:auction” has been forwarded to you for a decision.',
        ],
        'appeal_entity_decided' => [
            'subject' => 'Entity decision on an appeal — Mazayada',
            'line' => 'The entity issued its decision (:decision) on an appeal regarding the auction “:auction”, awaiting your confirmation.',
        ],
        'auction_report_referred' => [
            'subject' => 'Auction report referred to you — Mazayada',
            'line' => 'A report on the auction “:auction” has been referred to you; it is now available in the auction reports section.',
        ],
    ],
];
