<?php

return [
    'kyc_status' => [
        'PENDING' => 'Pending',
        'UNDER_REVIEW' => 'Under review',
        'COMPLETE' => 'Verified',
        'REJECTED' => 'Rejected',
        'SUSPENDED' => 'Suspended',
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

    'appeal_status' => [
        'SUBMITTED' => 'Submitted',
        'UNDER_REVIEW' => 'Under review',
        'RESOLVED' => 'Resolved',
        'REJECTED' => 'Rejected',
        'ESCALATED' => 'Escalated',
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
    ],

    'notification_channel' => [
        'PUSH' => 'Push',
        'SMS' => 'SMS',
        'EMAIL' => 'Email',
        'IN_APP' => 'In-app',
    ],
];
