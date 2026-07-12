<?php

return [
    // ===== Page =====
    'page_title' => 'السجل التجاري',
    'page_subtitle' => 'قدّم بيانات سجلك التجاري ووثائقه للمشاركة في المزايدات التي تتطلب سجلاً تجارياً',

    // ===== Sections =====
    'sec_data_title' => 'بيانات السجل التجاري',
    'sec_docs_title' => 'الوثائق المرفقة',

    // ===== Fields =====
    'f_company_name' => 'اسم الشركة / الكيان التجاري',
    'f_register_number' => 'رقم السجل التجاري',
    'f_tax_number' => 'الرقم الضريبي (البطاقة الضريبية)',
    'f_activity_type' => 'نوع النشاط التجاري',
    'f_expiry_date' => 'تاريخ انتهاء السجل التجاري',
    'f_register_document' => 'صورة السجل التجاري',
    'f_tax_card_document' => 'صورة البطاقة الضريبية',

    'view_current_file' => 'عرض الملف الحالي',
    'open_pdf' => 'فتح الملف (PDF)',
    'upload_hint' => 'PDF أو صورة JPG/PNG — حد أقصى 2 ميغابايت',
    'submit' => 'إرسال الطلب',

    // ===== Status banners =====
    'banner_none_title' => 'لم تُقدّم سجلك التجاري بعد',
    'banner_none_text' => 'املأ البيانات وارفع الوثائق أدناه لإرسال طلبك للمراجعة.',
    'banner_pending_title' => 'طلبك قيد المراجعة',
    'banner_pending_text' => 'تم إرسال طلبك بتاريخ :date وهو قيد المراجعة من قبل الإدارة.',
    'banner_approved_title' => 'تم اعتماد سجلك التجاري',
    'banner_approved_text' => 'سجلك التجاري معتمد، ويمكنك المشاركة في المزايدات التي تتطلب سجلاً تجارياً.',
    'banner_expired_text' => 'تنبيه: انتهت صلاحية سجلك التجاري — يرجى تحديثه لمواصلة المشاركة.',
    'banner_rejected_title' => 'تم رفض السجل التجاري',
    'banner_rejected_reason' => 'سبب الرفض:',
    'banner_rejected_hint' => 'يرجى تصحيح البيانات أو إعادة رفع الوثائق ثم إرسال الطلب من جديد.',

    // ===== Validation / flash =====
    'expiry_must_be_future' => 'يجب أن يكون تاريخ انتهاء السجل التجاري في المستقبل.',
    'submitted_success' => 'تم إرسال طلب السجل التجاري بنجاح، وهو الآن قيد المراجعة.',

    // ===== Notification copy (in-app, stored in the user's language) =====
    'notif_approved_title' => 'تم اعتماد سجلك التجاري',
    'notif_approved_body' => 'تمت الموافقة على سجلك التجاري. يمكنك الآن المشاركة في المزايدات التي تتطلب سجلاً تجارياً.',
    'notif_rejected_title' => 'تم رفض السجل التجاري',
    'notif_rejected_body' => 'تم رفض سجلك التجاري. السبب: :reason. يرجى التصحيح وإعادة الإرسال.',

    // ===== Verified-merchant badge (dashboard dropdown + sidebar) =====
    'badge_verified' => 'تاجر معتمد',
    'badge_verified_hint' => 'سجل تجاري معتمد وساري المفعول',
];
