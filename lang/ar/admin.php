<?php

return [
    // ===== Layout: sidebar + topbar =====
    'panel' => 'لوحة الإدارة',
    'nav_dashboard' => 'لوحة التحكم',
    'nav_auctions' => 'المزايدات',
    'nav_users' => 'المستخدمون',
    'nav_kyc' => 'التحقق (KYC)',
    'nav_appeals' => 'الطعون',
    'nav_audit' => 'سجل المراجعة',
    'create_auction' => 'إنشاء مزايدة',
    'logout' => 'تسجيل الخروج',
    'page_title_default' => 'لوحة التحكم',

    // ===== Dashboard: stat tiles =====
    'stat_total_users' => 'إجمالي المستخدمين',
    'stat_pending_kyc' => 'طلبات التحقق المعلّقة',
    'stat_active_auctions' => 'المزايدات النشطة',
    'stat_total_bids' => 'إجمالي العروض',

    // ===== Dashboard: tables =====
    'recent_auctions' => 'أحدث المزايدات',
    'recent_users' => 'أحدث المستخدمين',

    // Shared table headers
    'th_title' => 'العنوان',
    'th_entity' => 'الجهة',
    'th_category' => 'الفئة',
    'th_price' => 'السعر',
    'th_bids' => 'العروض',
    'th_status' => 'الحالة',
    'th_name' => 'الاسم',
    'th_email' => 'البريد الإلكتروني',
    'th_role' => 'الدور',
    'th_kyc' => 'حالة KYC',
    'th_registered' => 'التسجيل',

    // ===== Auctions management (index + create/edit form) =====
    'auctions' => [
        'manage_title' => 'إدارة المزايدات',
        'create_title' => 'إنشاء مزايدة جديدة',
        'edit_title' => 'تعديل المزايدة',
        'no_auctions' => 'لا توجد مزايدات',
        'publish' => 'نشر',
        'start' => 'بدء',
        'confirm_delete' => 'هل أنت متأكد من حذف هذه المزايدة؟',

        'sec_titles' => 'العناوين والأوصاف',
        'sec_classification' => 'التصنيف',
        'sec_pricing' => 'التسعير (بالدينار)',
        'sec_scheduling' => 'الجدولة',
        'sec_entity' => 'الجهة',
        'pricing_note' => 'يتم التحويل تلقائياً للسنتيم عند الإرسال',

        'f_title_ar' => 'العنوان بالعربية',
        'f_title_fr' => 'العنوان بالفرنسية',
        'f_description_ar' => 'الوصف بالعربية',
        'f_description_fr' => 'الوصف بالفرنسية',
        'f_category' => 'الفئة',
        'f_wilaya' => 'الولاية',
        'f_auction_type' => 'نوع المزايدة',
        'f_condition' => 'حالة الأصل',
        'f_asset_location' => 'موقع الأصل',
        'f_unit_count' => 'عدد الوحدات',
        'f_requires_cr' => 'يتطلب سجل تجاري',
        'f_lease_duration' => 'مدة الإيجار (سنوات)',
        'f_lease_renewals' => 'عدد التجديدات',
        'f_opening_price' => 'السعر الافتتاحي',
        'f_deposit' => 'مبلغ الضمان',
        'f_entry_fee' => 'رسوم المشاركة',
        'f_book_price' => 'ثمن دفتر الشروط',
        'f_start_time' => 'وقت البدء',
        'f_end_time' => 'وقت الانتهاء',
        'f_entity' => 'الجهة المنظمة',

        'choose_category' => '— اختر الفئة —',
        'choose_wilaya' => '— اختر الولاية —',
        'choose_type' => '— اختر النوع —',
        'choose_condition' => '— اختر الحالة —',
        'choose_entity' => '— اختر الجهة —',

        'submit_create' => 'إنشاء المزايدة',
        'submit_edit' => 'حفظ التعديلات',
    ],

    // ===== Users management =====
    'users' => [
        'manage_title' => 'إدارة المستخدمين',
        'th_account_status' => 'حالة الحساب',
        'blacklisted' => 'قائمة سوداء',
        'blacklist_action' => 'قائمة سوداء',
        'blacklist_reason_placeholder' => 'سبب الحظر...',
        'confirm_blacklist' => 'تأكيد الحظر',
        'confirm_blacklist_prompt' => 'هل أنت متأكد من إدراج هذا المستخدم في القائمة السوداء؟',
        'no_users' => 'لا يوجد مستخدمون',
    ],

    // ===== KYC review =====
    'kyc' => [
        'manage_title' => 'طلبات التحقق من الهوية',
        'th_email_short' => 'البريد',
        'th_registration_date' => 'تاريخ التسجيل',
        'approve' => 'قبول',
        'reject' => 'رفض',
        'reject_reason_placeholder' => 'سبب الرفض...',
        'confirm_reject' => 'تأكيد الرفض',
        'no_pending' => 'لا توجد طلبات تحقق معلّقة',
    ],

    // ===== Audit log =====
    'audit' => [
        'th_time' => 'التوقيت',
        'th_actor' => 'الفاعل',
        'th_action' => 'الإجراء',
        'th_resource' => 'المورد',
        'no_logs' => 'لا توجد سجلات',
    ],

    // ===== Controller flash / error messages =====
    'flash' => [
        'auction_created' => 'تم إنشاء المزاد بنجاح.',
        'auction_updated' => 'تم تحديث المزاد بنجاح.',
        'auction_deleted' => 'تم حذف المزاد بنجاح.',
        'auction_published' => 'تم نشر المزاد بنجاح.',
        'auction_started' => 'تم بدء المزاد بنجاح.',
        'auction_edit_only_draft' => 'لا يمكن تعديل المزاد إلا في حالة المسودة.',
        'auction_delete_has_bids' => 'لا يمكن حذف مزاد يحتوي على عروض.',
        'auction_publish_only_draft' => 'يجب أن يكون المزاد في حالة مسودة للنشر.',
        'auction_start_only_published' => 'يجب أن يكون المزاد منشوراً للبدء.',
        'kyc_approved' => 'تم قبول التوثيق بنجاح.',
        'kyc_rejected' => 'تم رفض التوثيق.',
        'user_blacklisted' => 'تم إدراج المستخدم في القائمة السوداء.',
    ],
];
