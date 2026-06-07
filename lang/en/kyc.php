<?php

return [
    // Middleware / guard messages (App\Http\Middleware\KycVerified).
    'complete_required' => 'You must complete identity verification before continuing.',
    'not_authorized' => 'You are not authorized to perform this action.',
    'flash_under_review' => 'Your identity verification request is currently under review.',
    'flash_rejected' => 'Your request was rejected — please correct your details and resubmit.',
    'flash_suspended' => 'Your account was suspended because identity verification was not completed in time.',
    'locked' => 'Editing is not possible while the request is under review or after approval.',

    // ===== Citizen KYC page =====
    'page_title' => 'Identity Verification (KYC)',
    'page_subtitle' => 'Complete your identity verification to take part in auctions',

    'step1_label' => 'Step one',
    'step1_title' => 'Biometric documents',
    'step2_label' => 'Step two',
    'step2_title' => 'Personal information',
    'step3_label' => 'Step three',
    'step3_title' => 'Submission & review',
    'step3_hint' => 'Review your details and documents, then submit the request for review by the administration.',

    // ===== Status banners =====
    'banner_pending_text' => 'You haven’t completed your identity verification yet — complete it to take part in auctions.',
    'banner_cta' => 'Complete verification',
    'banner_under_review_title' => 'Your request is under review',
    'banner_under_review_text' => 'Your request was submitted on :date and is being reviewed by the administration. You will be notified of the outcome soon.',
    'banner_complete_title' => 'Your identity is verified',
    'banner_complete_text' => 'Your identity is verified and you can take part in auctions.',
    'banner_rejected_title' => 'Verification request rejected',
    'banner_rejected_reason' => 'Rejection reason:',
    'banner_rejected_hint' => 'Please correct your details or re-upload your documents, then submit the request again.',
    'banner_suspended_title' => 'Account suspended',
    'banner_suspended_text' => 'Your account was suspended for not completing verification in time. Complete it to reactivate.',

    'doc_id_front' => 'ID card front',
    'doc_id_back' => 'ID card back',
    'doc_selfie' => 'Selfie with ID card',
    'uploaded' => 'Uploaded ✓',
    'uploaded_replace' => 'Uploaded ✓ — click to replace',
    'upload_hint' => 'JPG or PNG — max 2 MB',

    'requirements_title' => 'Photo requirements:',
    'req_clear' => 'Clear photo without reflections',
    'req_readable' => 'All corners and text legible',
    'req_size' => 'Maximum size: 2 MB per file',
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
    'f_expected_income' => 'Expected monthly income (DA)',
    'f_rip' => 'Bank account number (RIP)',

    'choose_wilaya' => '— Select wilaya —',
    'choose_wilaya_first' => '— Select a wilaya first —',
    'choose_commune' => '— Select commune —',
    'js_load_error' => 'Loading error',

    'submit' => 'Submit verification request',

    // ===== Controller flash / validation messages =====
    'file_type_not_allowed' => 'File type not allowed.',
    'file_uploaded' => 'File uploaded successfully.',
    'info_saved' => 'Personal information saved successfully.',
    'submitted_success' => 'Your verification request has been submitted and is now under review.',
    'error_docs_required' => 'You must upload all three documents (ID card front and back + selfie) before submitting.',
    'commune_wilaya_mismatch' => 'The selected commune does not belong to the chosen wilaya.',
    'postal_code_invalid' => 'The postal code must be 5 digits.',

    // ===== Notification copy (in-app, stored in the user's language) =====
    'notif_approved_title' => 'Your identity is verified',
    'notif_approved_body' => 'Your verification request has been approved. You can now take part in auctions.',
    'notif_rejected_title' => 'Verification request rejected',
    'notif_rejected_body' => 'Your verification request was rejected. Reason: :reason. Please correct and resubmit.',
    'notif_suspended_title' => 'Account suspended',
    'notif_suspended_body' => 'Your account was suspended because identity verification was not completed in time.',

    'doc_photo_biometric' => 'Biometric photo (optional)',
    'doc_photo_biometric_hint' => '35×45mm, white background, max 120KB',
    'f_id_type' => 'Identity document type',
    'id_type_none' => '— Choose type —',
    'f_id_number' => 'Identity document number',
    'f_nif' => 'Tax ID (NIF)',
    'f_nis' => 'Statistical ID (NIS)',
];
