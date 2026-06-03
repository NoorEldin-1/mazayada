<?php

return [
    // Middleware / guard messages (App\Http\Middleware\KycVerified).
    'complete_required' => 'يجب إكمال التحقق من الهوية قبل المتابعة.',
    'not_authorized' => 'غير مصرح لك بهذا الإجراء.',

    // ===== Citizen KYC page =====
    'page_title' => 'التحقق من الهوية (KYC)',
    'page_subtitle' => 'أكمل التحقق من هويتك للمشاركة في المزايدات',

    'step1_label' => 'الخطوة الأولى',
    'step1_title' => 'الوثائق البيومترية',
    'step2_label' => 'الخطوة الثانية',
    'step2_title' => 'المعلومات الشخصية',
    'step3_label' => 'الخطوة الثالثة',
    'step3_title' => 'الإرسال والمراجعة',

    'doc_id_front' => 'واجهة بطاقة الهوية',
    'doc_id_back' => 'خلفية بطاقة الهوية',
    'doc_selfie' => 'سيلفي مع بطاقة الهوية',
    'uploaded' => 'تم الرفع ✓',
    'upload_hint' => 'JPG أو PNG — حد أقصى 5 ميغابايت',

    'requirements_title' => 'متطلبات الصور:',
    'req_clear' => 'صورة واضحة بدون انعكاسات',
    'req_readable' => 'جميع الزوايا والنصوص مقروءة',
    'req_size' => 'الحجم الأقصى: 5 ميغابايت لكل ملف',
    'req_formats' => 'الصيغ المقبولة: JPG، PNG',

    'f_first_name_fr' => 'الاسم بالفرنسية',
    'f_last_name_fr' => 'اللقب بالفرنسية',
    'f_father_name' => 'اسم الأب',
    'f_mother_fullname' => 'اسم الأم الكامل',
    'f_wilaya' => 'الولاية',
    'f_commune' => 'البلدية',
    'f_full_address' => 'العنوان الكامل',
    'f_postal_code' => 'الرمز البريدي',
    'f_profession' => 'المهنة',
    'f_expected_income' => 'الدخل السنوي المتوقع (دج)',
    'f_rip' => 'رقم الحساب الجاري (RIP)',

    'choose_wilaya' => '— اختر الولاية —',
    'choose_wilaya_first' => '— اختر الولاية أولاً —',
    'choose_commune' => '— اختر البلدية —',
    'js_load_error' => 'خطأ في التحميل',

    'submit' => 'إرسال طلب التحقق',

    // ===== Controller flash messages =====
    'file_type_not_allowed' => 'نوع الملف غير مسموح.',
    'file_uploaded' => 'تم رفع الملف بنجاح.',
    'info_saved' => 'تم حفظ المعلومات الشخصية بنجاح.',
];
