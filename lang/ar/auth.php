<?php

return [
    // ===== Auth layout — marketing side panel =====
    'left_badge' => 'المنصة الوطنية للمزايدات',
    'left_title' => 'منصة المزايدات الرقمية الأولى في الجزائر',
    'left_desc' => 'انضم إلى آلاف المستخدمين الذين يشاركون في المزايدات العمومية بكل شفافية وأمان عبر منصتنا الرقمية.',
    'testimonial' => 'منصة مزايدة غيّرت تجربتنا في المشاركة في المزايدات العمومية. عملية شفافة وسهلة الاستخدام.',
    'testimonial_name' => 'محمد بن عمر',
    'testimonial_role' => 'مدير شركة مقاولات',
    'stat_active_auctions' => 'مزايدة نشطة',
    'stat_registered_users' => 'مستخدم مسجل',
    'stat_wilayas' => 'ولاية',

    // ===== Shared field labels & placeholders =====
    'nin_label' => 'رقم التعريف الوطني (NIN)',
    'login_id_label' => 'رقم التعريف الوطني أو البريد الإلكتروني',
    'login_id_placeholder' => 'رقم التعريف أو البريد الإلكتروني',
    'first_name_ar_label' => 'الاسم بالعربية',
    'first_name_placeholder' => 'الاسم',
    'last_name_ar_label' => 'اللقب بالعربية',
    'last_name_placeholder' => 'اللقب',
    'phone_label' => 'رقم الهاتف',
    'email_label' => 'البريد الإلكتروني',
    'birth_date_label' => 'تاريخ الميلاد',
    'password_label' => 'كلمة المرور',
    'new_password_label' => 'كلمة المرور الجديدة',
    'password_confirm_label' => 'تأكيد كلمة المرور',
    'otp_label' => 'رمز التحقق',

    // ===== Login =====
    'login_title' => 'تسجيل الدخول',
    'login_subtitle' => 'أدخل بياناتك للوصول إلى حسابك على منصة مزايدة.',
    'forgot_password' => 'نسيت كلمة المرور؟',
    'login_button' => 'تسجيل الدخول',
    'no_account' => 'ليس لديك حساب؟',
    'create_account_link' => 'أنشئ حساباً جديداً',

    // ===== Register =====
    'register_title' => 'إنشاء حساب',
    // :terms and :privacy are replaced with links from the view.
    'terms_agree' => 'أوافق على :terms و:privacy لمنصة مزايدة.',
    'terms_link' => 'شروط الاستخدام',
    'privacy_link' => 'سياسة الخصوصية',
    'register_button' => 'إنشاء الحساب',
    'have_account' => 'لديك حساب؟',
    'login_link' => 'تسجيل الدخول',

    // ===== Verify OTP =====
    'otp_title' => 'تأكيد رمز التحقق',
    'otp_subtitle' => 'أدخل الرمز المكوّن من 6 أرقام الذي أرسلناه إلى هاتفك أو بريدك الإلكتروني.',
    'otp_button' => 'تأكيد الرمز',
    'resend' => 'إعادة الإرسال',
    'resend_hint' => 'لم تستلم الرمز؟ تأكد من رقم هاتفك أو بريدك الإلكتروني.',

    // ===== Reset password =====
    'reset_title' => 'استعادة كلمة المرور',
    'reset_subtitle_request' => 'أدخل رقم التعريف الوطني والبريد الإلكتروني لاستلام رمز التحقق.',
    'reset_subtitle_confirm' => 'أدخل رمز التحقق وكلمة المرور الجديدة.',
    'change_password_button' => 'تغيير كلمة المرور',
    'send_otp_button' => 'إرسال رمز التحقق',
    'back_to_login' => 'العودة لتسجيل الدخول',

    // ===== Controller flash / error messages =====
    'too_many_attempts' => 'عدد المحاولات الفاشلة كبير. حاول مجدداً خلال :sec ثانية.',
    'account_locked' => 'الحساب مقفل. حاول مجدداً بعد :time',
    'invalid_credentials' => 'بيانات الدخول غير صحيحة.',
    'account_blocked' => 'تم حظر هذا الحساب.',
    'otp_invalid' => 'رمز التحقق غير صحيح أو منتهي الصلاحية.',
    'account_not_found' => 'لم يتم العثور على الحساب.',
    'otp_sent' => 'تم إرسال رمز التحقق.',
    'password_changed' => 'تم تغيير كلمة المرور بنجاح.',
    'password_mismatch' => 'كلمة المرور المدخلة لا تطابق كلمة مرورك الحالية.',
];
