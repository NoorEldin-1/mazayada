<?php

return [
    // Module + navigation
    'manage_title' => 'Auction reports',
    'none' => 'No reports yet.',

    // Row-action submenu (auctions table)
    'menu_report' => 'Auction report',
    'action_generate' => 'Issue report',
    'action_view' => 'View report',
    'action_refer' => 'Refer to entity',

    // Module table
    'th_seq' => 'No.',
    'th_auction' => 'Auction',
    'th_status' => 'Status',
    'th_generated_by' => 'Issued by',
    'th_referral' => 'Referral',
    'referred_badge' => 'Referred',
    'not_referred_badge' => 'Not referred',
    'confirm_refer' => 'Refer this report to the organizing entity? It will appear in their auction reports section.',

    // Flash messages
    'flash_referred' => 'The report was referred to the organizing entity.',
    'flash_no_report' => 'No report has been issued for this auction yet.',
    'flash_missing_file' => 'The report file could not be found.',
    'flash_generate_failed' => 'Failed to issue the report, please try again.',

    // ===== PDF document =====
    'doc_title' => 'Auction report No. :seq — :auction',
    'legal_notice' => 'This report is an electronically signed administrative document reflecting the auction data at the moment of issue. Its authenticity can be verified via the QR code.',
    'fees_note' => 'Breakdown of judicial fees and VAT (Decree 97-33) computed on the reference price below.',

    // Section 1 — identity
    'sec_identity' => 'Auction identity',
    'f_title_ar' => 'Title (Arabic)',
    'f_title_fr' => 'Title (French)',
    'f_title_en' => 'Title (English)',
    'f_id' => 'Auction ID',
    'f_entity' => 'Organizing entity',
    'f_category' => 'Category',
    'f_type' => 'Auction type',
    'f_asset_class' => 'Asset class',
    'f_condition' => 'Physical condition',
    'f_unit_count' => 'Unit count',
    'f_created_by' => 'Created by',

    // Section 2 — lifecycle
    'sec_lifecycle' => 'Timeline',
    'f_status' => 'Status',
    'f_start' => 'Start time',
    'f_end' => 'End time',
    'f_extensions' => 'Extensions',
    'f_closed_at' => 'Closed at',
    'f_settled_at' => 'Settled at',
    'f_inspection' => 'Inspection window',

    // Section 3 — financials
    'sec_financials' => 'Financials',
    'f_opening_price' => 'Opening price',
    'f_deposit' => 'Deposit amount',
    'f_entry_fee' => 'Entry fee',
    'f_book_price' => 'Condition book price',
    'f_current_price' => 'Current price',
    'f_final_price' => 'Final price',

    // Section 4 — bidding
    'sec_bidding' => 'Bidding summary',
    'f_bid_count' => 'Valid bids',
    'f_participants' => 'Participants',
    'th_bidder' => 'Bidder',
    'th_amount' => 'Amount',
    'th_time' => 'Time',
    'no_bids' => 'No bids.',

    // Section 5 — winner
    'sec_winner' => 'Winner',
    'f_winner_name' => 'Name',
    'f_winner_nin' => 'National ID number',
    'f_winner_phone' => 'Phone',

    // Section 6 — payments
    'sec_payments' => 'Payments ledger',
    'th_pay_type' => 'Payment type',
    'th_payer' => 'Payer',
    'th_status' => 'Status',
    'th_date' => 'Date',
    'no_payments' => 'No payments.',

    // Section 7 — documents
    'sec_documents' => 'Documents issued',
    'th_doc_type' => 'Type',
    'th_doc_title' => 'Title',
    'no_documents' => 'No documents.',

    // Section 8 — appeals
    'sec_appeals' => 'Appeals',
    'th_subject' => 'Subject',
    'no_appeals' => 'No appeals on this auction.',

    // Section 9 — delivery
    'sec_delivery' => 'Delivery',
    'f_delivery_status' => 'Delivery status',
    'f_delivery_date' => 'Delivery date',
    'no_delivery' => 'No delivery recorded.',

    // Section 10 — location
    'sec_location' => 'Asset location',
    'f_location' => 'Address',
    'f_wilaya' => 'Wilaya',
    'f_commune' => 'Commune',
    'f_coords' => 'Coordinates',

    // Section 11 — specifications
    'sec_specs' => 'Specifications',

    // Section 12 — issue metadata
    'sec_issue' => 'Issue metadata',
    'f_sequence' => 'Report No.',
    'f_issued_at' => 'Issued at',
];
