<?php

return [
    // Middleware / guard messages (App\Http\Middleware\KycVerified).
    'complete_required' => 'You must complete identity verification before continuing.',
    'not_authorized' => 'You are not authorized to perform this action.',

    // ===== Citizen KYC page =====
    'page_title' => 'Identity Verification (KYC)',
    'page_subtitle' => 'Complete your identity verification to take part in auctions',

    'step1_label' => 'Step one',
    'step1_title' => 'Biometric documents',
    'step2_label' => 'Step two',
    'step2_title' => 'Personal information',
    'step3_label' => 'Step three',
    'step3_title' => 'Submission & review',

    'doc_id_front' => 'ID card front',
    'doc_id_back' => 'ID card back',
    'doc_selfie' => 'Selfie with ID card',
    'uploaded' => 'Uploaded ✓',
    'upload_hint' => 'JPG or PNG — max 5 MB',

    'requirements_title' => 'Photo requirements:',
    'req_clear' => 'Clear photo without reflections',
    'req_readable' => 'All corners and text legible',
    'req_size' => 'Maximum size: 5 MB per file',
    'req_formats' => 'Accepted formats: JPG, PNG',

    'f_first_name_fr' => 'First name (French)',
    'f_last_name_fr' => 'Last name (French)',
    'f_father_name' => 'Father’s name',
    'f_mother_fullname' => 'Mother’s full name',
    'f_wilaya' => 'Wilaya',
    'f_commune' => 'Commune',
    'f_full_address' => 'Full address',
    'f_postal_code' => 'Postal code',
    'f_profession' => 'Profession',
    'f_expected_income' => 'Expected annual income (DA)',
    'f_rip' => 'Bank account number (RIP)',

    'choose_wilaya' => '— Select wilaya —',
    'choose_wilaya_first' => '— Select a wilaya first —',
    'choose_commune' => '— Select commune —',
    'js_load_error' => 'Loading error',

    'submit' => 'Submit verification request',

    // ===== Controller flash messages =====
    'file_type_not_allowed' => 'File type not allowed.',
    'file_uploaded' => 'File uploaded successfully.',
    'info_saved' => 'Personal information saved successfully.',
];
