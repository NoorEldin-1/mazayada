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
        'ENTITY_VIEWER' => 'حساب جهة (قراءة فقط)',
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

    'account_type' => [
        'PERSON' => 'شخص',
        'INSTITUTION' => 'جهة',
    ],

    'appeal_status' => [
        // PENDING/APPROVED/REJECTED double as the citizen-facing 3 states.
        'PENDING' => 'قيد المراجعة',
        'FORWARDED_TO_ENTITY' => 'محال إلى الجهة',
        'ENTITY_APPROVED' => 'وافقت الجهة',
        'ENTITY_REJECTED' => 'رفضت الجهة',
        'APPROVED' => 'موافقة',
        'REJECTED' => 'رفض',
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

    'payment_status' => [
        'PENDING' => 'قيد الانتظار',
        'CONFIRMED' => 'مؤكَّد',
        'REFUNDED' => 'مُسترَد',
        'FORFEITED' => 'مُصادَر',
        'FAILED' => 'فاشل',
    ],

    'id_document_type' => [
        'ID_CARD' => 'بطاقة التعريف الوطنية',
        'PASSPORT' => 'جواز السفر',
        'LICENSE' => 'رخصة السياقة',
    ],

    'asset_class' => [
        'MOVABLE' => 'منقول',
        'REAL_ESTATE' => 'عقار',
        'CUSTOMS' => 'بضائع جمركية',
    ],

    'delivery_status' => [
        'SCHEDULED' => 'مُجدولة',
        'IN_TRANSIT' => 'قيد النقل',
        'DELIVERED' => 'تم التسليم',
        'FAILED' => 'فشل التسليم',
        'CANCELLED' => 'ملغاة',
    ],

    'inspection_question_status' => [
        'PENDING' => 'في الانتظار',
        'ANSWERED' => 'تمت الإجابة',
        'REJECTED' => 'مرفوض',
    ],

    'document_type' => [
        'CONDITION_BOOK' => 'كراسة الشروط',
        'AWARD' => 'وثيقة الترسية',
        'PAYMENT_RECEIPT' => 'إيصال دفع',
        'DELIVERY_REPORT' => 'محضر التسليم',
    ],

    'notification_channel' => [
        'PUSH' => 'إشعار فوري',
        'SMS' => 'رسالة نصية',
        'EMAIL' => 'بريد إلكتروني',
        'IN_APP' => 'داخل التطبيق',
    ],
];
