<?php

return [
    // ===== Page =====
    'page_title' => 'Commercial Register',
    'page_subtitle' => 'Submit your commercial register data and documents to participate in auctions that require one',

    // ===== Sections =====
    'sec_data_title' => 'Commercial register data',
    'sec_docs_title' => 'Attached documents',

    // ===== Fields =====
    'f_company_name' => 'Company / commercial entity name',
    'f_register_number' => 'Commercial register number',
    'f_tax_number' => 'Tax number (tax card)',
    'f_activity_type' => 'Type of commercial activity',
    'f_expiry_date' => 'Commercial register expiry date',
    'f_register_document' => 'Commercial register scan',
    'f_tax_card_document' => 'Tax card scan',

    'view_current_file' => 'View current file',
    'open_pdf' => 'Open file (PDF)',
    'upload_hint' => 'PDF or JPG/PNG image — max 2 MB',
    'submit' => 'Submit request',

    // ===== Status banners =====
    'banner_none_title' => 'You have not submitted your commercial register yet',
    'banner_none_text' => 'Fill in the data and upload the documents below to send your request for review.',
    'banner_pending_title' => 'Your request is under review',
    'banner_pending_text' => 'Your request was submitted on :date and is under review by the administration.',
    'banner_approved_title' => 'Your commercial register is approved',
    'banner_approved_text' => 'Your commercial register is approved and you can participate in auctions that require one.',
    'banner_expired_text' => 'Note: your commercial register has expired — please update it to keep participating.',
    'banner_rejected_title' => 'Commercial register rejected',
    'banner_rejected_reason' => 'Reason for rejection:',
    'banner_rejected_hint' => 'Please correct the data or re-upload the documents, then submit the request again.',

    // ===== Validation / flash =====
    'expiry_must_be_future' => 'The commercial register expiry date must be in the future.',
    'submitted_success' => 'Your commercial register request was submitted successfully and is now under review.',

    // ===== Notification copy (in-app, stored in the user's language) =====
    'notif_approved_title' => 'Your commercial register is approved',
    'notif_approved_body' => 'Your commercial register has been approved. You can now participate in auctions that require one.',
    'notif_rejected_title' => 'Commercial register rejected',
    'notif_rejected_body' => 'Your commercial register was rejected. Reason: :reason. Please correct and resubmit.',

    // ===== Verified-merchant badge (dashboard dropdown + sidebar) =====
    'badge_verified' => 'Verified Merchant',
    'badge_verified_hint' => 'Verified, in-date commercial register',
];
