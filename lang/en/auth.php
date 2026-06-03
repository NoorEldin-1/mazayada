<?php

return [
    // ===== Auth layout — marketing side panel =====
    'left_badge' => 'National Auctions Platform',
    'left_title' => 'Algeria’s first digital auctions platform',
    'left_desc' => 'Join thousands of users taking part in public auctions transparently and securely through our digital platform.',
    'testimonial' => 'Mazayada transformed how we take part in public auctions. The process is transparent and easy to use.',
    'testimonial_name' => 'Mohamed Ben Omar',
    'testimonial_role' => 'Construction company manager',
    'stat_active_auctions' => 'active auctions',
    'stat_registered_users' => 'registered users',
    'stat_wilayas' => 'wilayas',

    // ===== Marketing side panel — instructions carousel =====
    'carousel' => [
        'aria_label' => 'Platform instructions and terms slides',
        'prev' => 'Previous slide',
        'next' => 'Next slide',
        'go_to' => 'Go to slide :num',

        // Slide 1 — entry rules
        'input_title' => 'Entry rules',
        'input_nin' => 'National ID Number (NIN): exactly 18 digits.',
        'input_phone' => 'Phone: 10 digits starting with 05, 06, or 07.',
        'input_age' => 'Participation is open to those aged 18 and over.',

        // Slide 2 — KYC
        'kyc_title' => 'Identity verification (KYC)',
        'kyc_body' => 'After registration, identity verification is mandatory: upload your documents and a selfie. Complete it on time to avoid account suspension.',

        // Slide 3 — security & data protection
        'security_title' => 'Security & data protection',
        'security_body' => 'A secure government platform compliant with Algeria’s data protection law (18-07). Your data stays in Algeria and is never transferred abroad.',

        // Slide 4 — how to join auctions
        'auction_title' => 'How to join auctions',
        'auction_body' => 'To enter any auction, pay an entry fee and a (refundable) security deposit by bank card, then bid directly.',
    ],

    // ===== Shared field labels & placeholders =====
    'nin_label' => 'National ID Number (NIN)',
    'login_id_label' => 'National ID number or email',
    'login_id_placeholder' => 'National ID or email',
    'first_name_ar_label' => 'First name (Arabic)',
    'first_name_placeholder' => 'First name',
    'last_name_ar_label' => 'Last name (Arabic)',
    'last_name_placeholder' => 'Last name',
    'phone_label' => 'Phone number',
    'email_label' => 'Email address',
    'birth_date_label' => 'Date of birth',
    'password_label' => 'Password',
    'new_password_label' => 'New password',
    'password_confirm_label' => 'Confirm password',
    'otp_label' => 'Verification code',

    // ===== Login =====
    'login_title' => 'Log in',
    'login_subtitle' => 'Enter your details to access your Mazayada account.',
    'forgot_password' => 'Forgot your password?',
    'login_button' => 'Log in',
    'no_account' => 'Don’t have an account?',
    'create_account_link' => 'Create a new account',

    // ===== Register =====
    'register_title' => 'Create an account',
    // :terms and :privacy are replaced with links from the view.
    'terms_agree' => 'I agree to Mazayada’s :terms and :privacy.',
    'terms_link' => 'Terms of Use',
    'privacy_link' => 'Privacy Policy',
    'register_button' => 'Create account',
    'have_account' => 'Already have an account?',
    'login_link' => 'Log in',

    // ===== Verify OTP =====
    'otp_title' => 'Confirm verification code',
    'otp_subtitle' => 'Enter the 6-digit code we sent to your email.',
    'otp_button' => 'Confirm code',
    'resend' => 'Resend',
    'resend_hint' => 'Didn’t receive the code? Check your email or your spam folder.',

    // ===== Reset password =====
    'reset_title' => 'Reset password',
    'reset_subtitle_request' => 'Enter your national ID number and email to receive a verification code.',
    'reset_subtitle_confirm' => 'Enter the verification code and your new password.',
    'change_password_button' => 'Change password',
    'send_otp_button' => 'Send verification code',
    'back_to_login' => 'Back to login',

    // ===== Controller flash / error messages =====
    'too_many_attempts' => 'Too many failed attempts. Please try again in :sec seconds.',
    'account_locked' => 'Account locked. Please try again :time',
    'invalid_credentials' => 'Invalid login credentials.',
    'account_blocked' => 'This account has been blocked.',
    'otp_invalid' => 'The verification code is incorrect or has expired.',
    'otp_resent' => 'A new verification code has been sent to your email.',
    'otp_resend_cooldown' => 'Please wait :sec seconds before requesting a new code.',
    'otp_too_many_attempts' => 'Too many attempts. Please request a new code.',
    'verify_email_first' => 'You must verify your email first. We have sent you a new code.',
    'account_not_found' => 'Account not found.',
    'otp_sent' => 'Verification code sent.',
    'password_changed' => 'Your password has been changed successfully.',
    'password_mismatch' => 'The provided password does not match your current password.',
];
