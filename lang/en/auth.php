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
    'otp_subtitle' => 'Enter the 6-digit code we sent to your phone or email.',
    'otp_button' => 'Confirm code',
    'resend' => 'Resend',
    'resend_hint' => 'Didn’t receive the code? Check your phone number or email.',

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
    'account_not_found' => 'Account not found.',
    'otp_sent' => 'Verification code sent.',
    'password_changed' => 'Your password has been changed successfully.',
    'password_mismatch' => 'The provided password does not match your current password.',
];
