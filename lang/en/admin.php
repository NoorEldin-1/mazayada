<?php

return [
    // ===== Layout: sidebar + topbar =====
    'panel' => 'Admin Panel',
    'nav_dashboard' => 'Dashboard',
    'nav_auctions' => 'Auctions',
    'nav_users' => 'Users',
    'nav_kyc' => 'Verification (KYC)',
    'nav_appeals' => 'Appeals',
    'nav_audit' => 'Audit Log',
    'create_auction' => 'Create Auction',
    'logout' => 'Log out',
    'page_title_default' => 'Dashboard',

    // ===== Dashboard: stat tiles =====
    'stat_total_users' => 'Total users',
    'stat_pending_kyc' => 'Pending verification requests',
    'stat_active_auctions' => 'Active auctions',
    'stat_total_bids' => 'Total bids',

    // ===== Dashboard: tables =====
    'recent_auctions' => 'Latest auctions',
    'recent_users' => 'Latest users',

    // Shared table headers
    'th_title' => 'Title',
    'th_entity' => 'Entity',
    'th_category' => 'Category',
    'th_price' => 'Price',
    'th_bids' => 'Bids',
    'th_status' => 'Status',
    'th_name' => 'Name',
    'th_email' => 'Email',
    'th_role' => 'Role',
    'th_kyc' => 'KYC status',
    'th_registered' => 'Registered',

    // ===== Auctions management (index + create/edit form) =====
    'auctions' => [
        'manage_title' => 'Manage Auctions',
        'create_title' => 'Create New Auction',
        'edit_title' => 'Edit Auction',
        'no_auctions' => 'No auctions',
        'publish' => 'Publish',
        'start' => 'Start',
        'confirm_delete' => 'Are you sure you want to delete this auction?',

        'sec_titles' => 'Titles & Descriptions',
        'sec_classification' => 'Classification',
        'sec_pricing' => 'Pricing (in dinars)',
        'sec_scheduling' => 'Scheduling',
        'sec_entity' => 'Entity',
        'pricing_note' => 'Automatically converted to centimes on submission',

        'f_title_ar' => 'Title (Arabic)',
        'f_title_fr' => 'Title (French)',
        'f_description_ar' => 'Description (Arabic)',
        'f_description_fr' => 'Description (French)',
        'f_category' => 'Category',
        'f_wilaya' => 'Wilaya',
        'f_auction_type' => 'Auction type',
        'f_condition' => 'Asset condition',
        'f_asset_location' => 'Asset location',
        'f_unit_count' => 'Unit count',
        'f_requires_cr' => 'Requires commercial register',
        'f_lease_duration' => 'Lease duration (years)',
        'f_lease_renewals' => 'Number of renewals',
        'f_opening_price' => 'Opening price',
        'f_deposit' => 'Deposit amount',
        'f_entry_fee' => 'Entry fee',
        'f_book_price' => 'Specifications booklet price',
        'f_start_time' => 'Start time',
        'f_end_time' => 'End time',
        'f_entity' => 'Organizing entity',

        'choose_category' => '— Select category —',
        'choose_wilaya' => '— Select wilaya —',
        'choose_type' => '— Select type —',
        'choose_condition' => '— Select condition —',
        'choose_entity' => '— Select entity —',

        'submit_create' => 'Create auction',
        'submit_edit' => 'Save changes',
    ],

    // ===== Users management =====
    'users' => [
        'manage_title' => 'Manage Users',
        'th_account_status' => 'Account status',
        'blacklisted' => 'Blacklisted',
        'blacklist_action' => 'Blacklist',
        'blacklist_reason_placeholder' => 'Reason for blacklisting...',
        'confirm_blacklist' => 'Confirm blacklist',
        'confirm_blacklist_prompt' => 'Are you sure you want to blacklist this user?',
        'no_users' => 'No users',
    ],

    // ===== KYC review =====
    'kyc' => [
        'manage_title' => 'Identity Verification Requests',
        'th_email_short' => 'Email',
        'th_registration_date' => 'Registration date',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'reject_reason_placeholder' => 'Reason for rejection...',
        'confirm_reject' => 'Confirm rejection',
        'no_pending' => 'No pending verification requests',
    ],

    // ===== Audit log =====
    'audit' => [
        'th_time' => 'Timestamp',
        'th_actor' => 'Actor',
        'th_action' => 'Action',
        'th_resource' => 'Resource',
        'no_logs' => 'No logs',
    ],

    // ===== Controller flash / error messages =====
    'flash' => [
        'auction_created' => 'Auction created successfully.',
        'auction_updated' => 'Auction updated successfully.',
        'auction_deleted' => 'Auction deleted successfully.',
        'auction_published' => 'Auction published successfully.',
        'auction_started' => 'Auction started successfully.',
        'auction_edit_only_draft' => 'The auction can only be edited while in draft status.',
        'auction_delete_has_bids' => 'An auction that has bids cannot be deleted.',
        'auction_publish_only_draft' => 'The auction must be in draft status to be published.',
        'auction_start_only_published' => 'The auction must be published to be started.',
        'kyc_approved' => 'Verification approved successfully.',
        'kyc_rejected' => 'Verification rejected.',
        'user_blacklisted' => 'The user has been blacklisted.',
    ],
];
