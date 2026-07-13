<?php

return [
    // Module + navigation
    'manage_title' => 'تقارير المزادات',
    'none' => 'لا توجد تقارير بعد.',

    // Row-action submenu (auctions table)
    'menu_report' => 'تقرير المزاد',
    'action_generate' => 'إصدار تقرير',
    'action_view' => 'رؤية التقرير',
    'action_refer' => 'إحالة للجهة',

    // Module table
    'th_seq' => 'الرقم',
    'th_auction' => 'المزاد',
    'th_status' => 'الحالة',
    'th_generated_by' => 'أصدره',
    'th_referral' => 'الإحالة',
    'referred_badge' => 'محال للجهة',
    'not_referred_badge' => 'غير محال',
    'confirm_refer' => 'هل تريد إحالة هذا التقرير إلى الجهة المنظمة؟ سيظهر لها في قسم تقارير المزادات.',

    // Flash messages
    'flash_referred' => 'تمت إحالة التقرير إلى الجهة المنظمة.',
    'flash_no_report' => 'لم يُصدر أي تقرير لهذا المزاد بعد.',
    'flash_missing_file' => 'تعذّر العثور على ملف التقرير.',
    'flash_generate_failed' => 'تعذّر إصدار التقرير، يرجى المحاولة مرة أخرى.',

    // ===== PDF document =====
    'doc_title' => 'تقرير المزاد رقم :seq — :auction',
    'legal_notice' => 'هذا التقرير وثيقة إدارية موقّعة إلكترونيًا تعكس بيانات المزاد لحظة إصدارها. يمكن التحقّق من صحّتها عبر رمز الاستجابة السريعة.',
    'fees_note' => 'تفصيل الرسوم القضائية والضريبة على القيمة المضافة (المرسوم 97-33) محسوبة على السعر المرجعي أدناه.',

    // Section 1 — identity
    'sec_identity' => 'بيانات المزاد',
    'f_title_ar' => 'العنوان (عربي)',
    'f_title_fr' => 'العنوان (فرنسي)',
    'f_title_en' => 'العنوان (إنجليزي)',
    'f_id' => 'معرّف المزاد',
    'f_entity' => 'الجهة المنظمة',
    'f_category' => 'الفئة',
    'f_type' => 'نوع المزاد',
    'f_asset_class' => 'صنف الأصل',
    'f_condition' => 'الحالة الفيزيائية',
    'f_unit_count' => 'عدد الوحدات',
    'f_created_by' => 'أنشأه',

    // Section 2 — lifecycle
    'sec_lifecycle' => 'المسار الزمني',
    'f_status' => 'الحالة',
    'f_start' => 'وقت البداية',
    'f_end' => 'وقت النهاية',
    'f_extensions' => 'عدد التمديدات',
    'f_closed_at' => 'وقت الإغلاق',
    'f_settled_at' => 'وقت التسوية',
    'f_inspection' => 'نافذة المعاينة',

    // Section 3 — financials
    'sec_financials' => 'البيانات المالية',
    'f_opening_price' => 'سعر الافتتاح',
    'f_deposit' => 'مبلغ التأمين',
    'f_entry_fee' => 'رسم الدخول',
    'f_book_price' => 'ثمن كراسة الشروط',
    'f_current_price' => 'السعر الحالي',
    'f_final_price' => 'السعر النهائي',

    // Section 4 — bidding
    'sec_bidding' => 'ملخّص المزايدة',
    'f_bid_count' => 'عدد العروض الصحيحة',
    'f_participants' => 'عدد المشاركين',
    'th_bidder' => 'المزايد',
    'th_amount' => 'المبلغ',
    'th_time' => 'الوقت',
    'no_bids' => 'لا توجد عروض.',

    // Section 5 — winner
    'sec_winner' => 'الفائز',
    'f_winner_name' => 'الاسم',
    'f_winner_nin' => 'رقم التعريف الوطني',
    'f_winner_phone' => 'الهاتف',

    // Section 6 — payments
    'sec_payments' => 'سجل المدفوعات',
    'th_pay_type' => 'نوع الدفعة',
    'th_payer' => 'الدافع',
    'th_status' => 'الحالة',
    'th_date' => 'التاريخ',
    'no_payments' => 'لا توجد مدفوعات.',

    // Section 7 — documents
    'sec_documents' => 'المستندات الصادرة',
    'th_doc_type' => 'النوع',
    'th_doc_title' => 'العنوان',
    'no_documents' => 'لا توجد مستندات.',

    // Section 8 — appeals
    'sec_appeals' => 'الطعون',
    'th_subject' => 'الموضوع',
    'no_appeals' => 'لا توجد طعون على هذا المزاد.',

    // Section 9 — delivery
    'sec_delivery' => 'التسليم',
    'f_delivery_status' => 'حالة التسليم',
    'f_delivery_date' => 'تاريخ التسليم',
    'no_delivery' => 'لم تُسجَّل عملية تسليم.',

    // Section 10 — location
    'sec_location' => 'موقع الأصل',
    'f_location' => 'العنوان',
    'f_wilaya' => 'الولاية',
    'f_commune' => 'البلدية',
    'f_coords' => 'الإحداثيات',

    // Section 11 — specifications
    'sec_specs' => 'المواصفات',

    // Section 12 — issue metadata
    'sec_issue' => 'بيانات الإصدار',
    'f_sequence' => 'رقم التقرير',
    'f_issued_at' => 'تاريخ الإصدار',
];
