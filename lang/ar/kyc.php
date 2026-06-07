<?php

return [
    // Middleware / guard messages (App\Http\Middleware\KycVerified).
    'complete_required' => 'يجب إكمال التحقق من الهوية قبل المتابعة.',
    'not_authorized' => 'غير مصرح لك بهذا الإجراء.',
    'flash_under_review' => 'طلب التحقق من هويتك قيد المراجعة حالياً.',
    'flash_rejected' => 'تم رفض طلب التحقق — يرجى تصحيح البيانات وإعادة الإرسال.',
    'flash_suspended' => 'تم تعليق حسابك لعدم إكمال التحقق من الهوية في الوقت المحدد.',
    'locked' => 'لا يمكن التعديل أثناء مراجعة الطلب أو بعد توثيقه.',

    // ===== Citizen KYC page =====
    'page_title' => 'التحقق من الهوية (KYC)',
    'page_subtitle' => 'أكمل التحقق من هويتك للمشاركة في المزايدات',

    'step1_label' => 'الخطوة الأولى',
    'step1_title' => 'الوثائق البيومترية',
    'step2_label' => 'الخطوة الثانية',
    'step2_title' => 'المعلومات الشخصية',
    'step3_label' => 'الخطوة الثالثة',
    'step3_title' => 'الإرسال والمراجعة',
    'step3_hint' => 'راجع بياناتك ووثائقك ثم أرسل الطلب للمراجعة من قبل الإدارة.',

    // ===== Status banners =====
    'banner_pending_text' => 'لم تكمل التحقق من هويتك بعد — أكمله للمشاركة في المزايدات.',
    'banner_cta' => 'إكمال التحقق',
    'banner_under_review_title' => 'طلبك قيد المراجعة',
    'banner_under_review_text' => 'تم إرسال طلبك بتاريخ :date وهو قيد المراجعة من قبل الإدارة. ستصلك نتيجة التحقق قريباً.',
    'banner_complete_title' => 'تم توثيق هويتك',
    'banner_complete_text' => 'هويتك موثّقة ويمكنك المشاركة في المزايدات.',
    'banner_rejected_title' => 'تم رفض طلب التحقق',
    'banner_rejected_reason' => 'سبب الرفض:',
    'banner_rejected_hint' => 'يرجى تصحيح البيانات أو إعادة رفع الوثائق ثم إرسال الطلب من جديد.',
    'banner_suspended_title' => 'تم تعليق الحساب',
    'banner_suspended_text' => 'تم تعليق حسابك لعدم إكمال التحقق في الوقت المحدد. أكمل التحقق لإعادة التفعيل.',

    'doc_id_front' => 'واجهة بطاقة الهوية',
    'doc_id_back' => 'خلفية بطاقة الهوية',
    'doc_selfie' => 'سيلفي مع بطاقة الهوية',
    'uploaded' => 'تم الرفع ✓',
    'uploaded_replace' => 'تم الرفع ✓ — اضغط للاستبدال',
    'upload_hint' => 'JPG أو PNG — حد أقصى 2 ميغابايت',

    'requirements_title' => 'متطلبات الصور:',
    'req_clear' => 'صورة واضحة بدون انعكاسات',
    'req_readable' => 'جميع الزوايا والنصوص مقروءة',
    'req_size' => 'الحجم الأقصى: 2 ميغابايت لكل ملف',
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
    'f_expected_income' => 'الدخل الشهري المتوقع (دج)',
    'f_rip' => 'رقم الحساب الجاري (RIP)',

    'choose_wilaya' => '— اختر الولاية —',
    'choose_wilaya_first' => '— اختر الولاية أولاً —',
    'choose_commune' => '— اختر البلدية —',
    'js_load_error' => 'خطأ في التحميل',

    'submit' => 'إرسال طلب التحقق',

    // ===== Controller flash / validation messages =====
    'file_type_not_allowed' => 'نوع الملف غير مسموح.',
    'file_uploaded' => 'تم رفع الملف بنجاح.',
    'info_saved' => 'تم حفظ المعلومات الشخصية بنجاح.',
    'submitted_success' => 'تم إرسال طلب التحقق بنجاح، وهو الآن قيد المراجعة.',
    'error_docs_required' => 'يجب رفع جميع الوثائق الثلاث (واجهة وخلفية بطاقة الهوية + سيلفي) قبل الإرسال.',
    'commune_wilaya_mismatch' => 'البلدية المختارة لا تتبع الولاية المحددة.',
    'postal_code_invalid' => 'الرمز البريدي يجب أن يتكوّن من 5 أرقام.',

    // ===== Notification copy (in-app, stored in the user's language) =====
    'notif_approved_title' => 'تم توثيق هويتك',
    'notif_approved_body' => 'تمت الموافقة على طلب التحقق من هويتك. يمكنك الآن المشاركة في المزايدات.',
    'notif_rejected_title' => 'تم رفض طلب التحقق',
    'notif_rejected_body' => 'تم رفض طلب التحقق من هويتك. السبب: :reason. يرجى التصحيح وإعادة الإرسال.',
    'notif_suspended_title' => 'تم تعليق الحساب',
    'notif_suspended_body' => 'تم تعليق حسابك لعدم إكمال التحقق من الهوية في الوقت المحدد.',

    'doc_photo_biometric' => 'صورة بيومترية (اختياري)',
    'doc_photo_biometric_hint' => '35×45مم، خلفية بيضاء، أقصى 120 ك.ب',
    'f_id_type' => 'نوع وثيقة الهوية',
    'id_type_none' => '— اختر النوع —',
    'f_id_number' => 'رقم وثيقة الهوية',
    'f_nif' => 'الرقم الجبائي (NIF)',
    'f_nis' => 'الرقم الإحصائي (NIS)',
];
