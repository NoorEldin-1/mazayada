<?php

return [
    'kyc_status' => [
        'PENDING' => 'في الانتظار',
        'UNDER_REVIEW' => 'قيد المراجعة',
        'COMPLETE' => 'موثّق',
        'REJECTED' => 'مرفوض',
        'SUSPENDED' => 'معلّق',
    ],

    'auction_status' => [
        'DRAFT' => 'مسودة',
        'PUBLISHED' => 'منشورة',
        'ACTIVE' => 'نشطة',
        'EXTENDED' => 'ممددة',
        'CLOSED' => 'مغلقة',
        'CANCELLED' => 'ملغاة',
    ],

    'user_role' => [
        'CITIZEN' => 'مواطن',
        'PREMIUM_CITIZEN' => 'مواطن مميز',
        'SUPER_ADMIN' => 'مشرف عام',
        'ENTITY_HEAD' => 'رئيس جهة',
        'APPRAISER' => 'خبير تقييم',
        'HUISSIER' => 'محضر قضائي',
        'COMMITTEE_MEMBER' => 'عضو لجنة',
        'CONTENT_ADMIN' => 'مشرف محتوى',
    ],

    'auction_type' => [
        'SALE' => 'بيع',
        'LEASE' => 'إيجار',
    ],

    'asset_condition' => [
        'NEW' => 'جديد',
        'GOOD' => 'جيد',
        'FAIR' => 'مقبول',
        'POOR' => 'ضعيف',
        'SCRAP' => 'خردة',
    ],

    'account_status' => [
        'ACTIVE' => 'نشط',
        'SUSPENDED' => 'معلّق',
        'BANNED' => 'محظور',
    ],

    'appeal_status' => [
        'SUBMITTED' => 'مقدّم',
        'UNDER_REVIEW' => 'قيد المراجعة',
        'RESOLVED' => 'تمت التسوية',
        'REJECTED' => 'مرفوض',
        'ESCALATED' => 'مُصعَّد',
    ],

    'entity_type' => [
        'CUSTOMS' => 'المديرية العامة للجمارك',
        'STATE_PROPERTIES' => 'أملاك الدولة',
        'MUNICIPALITY' => 'المجالس البلدية',
        'JUDICIAL' => 'المحضرون القضائيون',
        'TAX' => 'المديرية العامة للضرائب',
    ],

    'payment_type' => [
        'DEPOSIT' => 'كفالة',
        'ENTRY_FEE' => 'رسوم دخول',
        'BOOK_PURCHASE' => 'كراسة شروط',
        'FINAL_PAYMENT' => 'دفع نهائي',
    ],

    'id_document_type' => [
        'ID_CARD' => 'بطاقة التعريف الوطنية',
        'PASSPORT' => 'جواز السفر',
        'LICENSE' => 'رخصة السياقة',
    ],
];
