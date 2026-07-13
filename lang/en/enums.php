<?php

return [
    'kyc_status' => [
        'PENDING' => 'Pending',
        'UNDER_REVIEW' => 'Under review',
        'COMPLETE' => 'Verified',
        'REJECTED' => 'Rejected',
        'SUSPENDED' => 'Suspended',
    ],

    'commercial_register_status' => [
        'PENDING' => 'Under review',
        'APPROVED' => 'Approved',
        'REJECTED' => 'Rejected',
    ],

    'auction_status' => [
        'DRAFT' => 'Draft',
        'PUBLISHED' => 'Published',
        'ACTIVE' => 'Active',
        'EXTENDED' => 'Extended',
        'CLOSED' => 'Closed',
        'CANCELLED' => 'Cancelled',
    ],

    'user_role' => [
        'CITIZEN' => 'Citizen',
        'PREMIUM_CITIZEN' => 'Premium Citizen',
        'SUPER_ADMIN' => 'Super Admin',
        'ENTITY_HEAD' => 'Entity Head',
        'APPRAISER' => 'Appraiser',
        'HUISSIER' => 'Judicial Officer',
        'COMMITTEE_MEMBER' => 'Committee Member',
        'CONTENT_ADMIN' => 'Content Admin',
        'ENTITY_VIEWER' => 'Entity Account (read-only)',
    ],

    'auction_type' => [
        'SALE' => 'Sale',
        'LEASE' => 'Lease',
    ],

    'asset_condition' => [
        'NEW' => 'New',
        'GOOD' => 'Good',
        'FAIR' => 'Fair',
        'POOR' => 'Poor',
        'SCRAP' => 'Scrap',
    ],

    'account_status' => [
        'ACTIVE' => 'Active',
        'SUSPENDED' => 'Suspended',
        'BANNED' => 'Banned',
    ],

    'account_type' => [
        'PERSON' => 'Person',
        'INSTITUTION' => 'Entity',
    ],

    'appeal_status' => [
        // PENDING/APPROVED/REJECTED double as the citizen-facing 3 states.
        'PENDING' => 'Under review',
        'FORWARDED_TO_ENTITY' => 'Forwarded to entity',
        'ENTITY_APPROVED' => 'Approved by entity',
        'ENTITY_REJECTED' => 'Rejected by entity',
        'APPROVED' => 'Approved',
        'REJECTED' => 'Rejected',
    ],

    'entity_type' => [
        'CUSTOMS' => 'Directorate General of Customs',
        'STATE_PROPERTIES' => 'State Property',
        'MUNICIPALITY' => 'Municipal Councils',
        'JUDICIAL' => 'Judicial Officers',
        'TAX' => 'Directorate General of Taxes',
    ],

    'payment_type' => [
        'DEPOSIT' => 'Deposit',
        'ENTRY_FEE' => 'Entry fee',
        'BOOK_PURCHASE' => 'Specifications booklet',
        'FINAL_PAYMENT' => 'Final payment',
    ],

    'payment_status' => [
        'PENDING' => 'Pending',
        'CONFIRMED' => 'Confirmed',
        'REFUNDED' => 'Refunded',
        'FORFEITED' => 'Forfeited',
        'FAILED' => 'Failed',
    ],

    'id_document_type' => [
        'ID_CARD' => 'National ID card',
        'PASSPORT' => 'Passport',
        'LICENSE' => 'Driver\'s license',
    ],

    'asset_class' => [
        'MOVABLE' => 'Movable',
        'REAL_ESTATE' => 'Real estate',
        'CUSTOMS' => 'Customs goods',
    ],

    'delivery_status' => [
        'SCHEDULED' => 'Scheduled',
        'IN_TRANSIT' => 'In transit',
        'DELIVERED' => 'Delivered',
        'FAILED' => 'Failed',
        'CANCELLED' => 'Cancelled',
    ],

    'inspection_question_status' => [
        'PENDING' => 'Pending',
        'ANSWERED' => 'Answered',
        'REJECTED' => 'Rejected',
    ],

    'document_type' => [
        'CONDITION_BOOK' => 'Condition book',
        'AWARD' => 'Award document',
        'PAYMENT_RECEIPT' => 'Payment receipt',
        'DELIVERY_REPORT' => 'Delivery report',
        'AUCTION_REPORT' => 'Auction report',
    ],

    'notification_channel' => [
        'PUSH' => 'Push',
        'SMS' => 'SMS',
        'EMAIL' => 'Email',
        'IN_APP' => 'In-app',
    ],
];
