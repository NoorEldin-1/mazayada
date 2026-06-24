<?php

return [
    // ===== Admin / entity: appeals management =====
    'manage_title' => 'إدارة الطعون',
    'th_id' => 'الرقم',
    'th_user' => 'المستخدم',
    'th_auction' => 'المزايدة',
    'th_subject' => 'الموضوع',
    'reason_label' => 'السبب:',
    'response_label' => 'الرد',
    'response_placeholder' => 'اكتب ردك هنا...',
    'none' => 'لا توجد طعون',

    // Workflow actions
    'forward_btn' => 'إحالة إلى الجهة المنظمة',
    'reject_intake_label' => 'سبب الرفض',
    'reject_intake_btn' => 'رفض مباشر',
    'awaiting_entity' => 'بانتظار قرار الجهة المنظمة.',
    'entity_decision_label' => 'قرار الجهة:',
    'entity_response_label' => 'رد الجهة:',
    'entity_response_field' => 'رد الجهة',
    'entity_response_placeholder' => 'اكتب مبرر القرار...',
    'entity_no_action' => 'لا يوجد إجراء مطلوب على هذا الطعن حاليًا.',
    'final_decision_label' => 'القرار النهائي',
    'decision_approve' => 'موافقة',
    'decision_reject' => 'رفض',
    'confirm_btn' => 'تأكيد القرار',

    // ===== Citizen: appeal on the auction page =====
    'tab' => 'الطعون',
    'auction_tab_title' => 'تقديم طعن على نتيجة المزايدة',
    'your_appeal_title' => 'طعنك على هذه المزايدة',
    'window_hint' => 'يمكنك تقديم طعن واحد خلال :days يومًا من إغلاق المزايدة.',
    'subject' => 'الموضوع',
    'subject_placeholder' => 'عنوان الطعن',
    'details' => 'التفاصيل',
    'details_placeholder' => 'اشرح سبب الطعن بالتفصيل...',
    'submit' => 'إرسال الطعن',
    'submitted_on' => 'قُدّم في',

    // ===== Citizen: my appeals (tracking) =====
    'submitted_list' => 'الطعون المقدّمة',
    'admin_response' => 'رد الإدارة:',
    'auction_ref' => 'المزايدة:',
    'none_submitted' => 'لا توجد طعون مقدّمة',
    'file_from_auction_hint' => 'تُقدَّم الطعون من صفحة المزايدة بعد إغلاقها.',

    // ===== Flash + error messages =====
    'flash_submitted' => 'تم تقديم الطعن بنجاح.',
    'flash_forwarded' => 'تمت إحالة الطعن إلى الجهة المنظمة.',
    'flash_entity_decided' => 'تم تسجيل قرار الجهة بنجاح.',
    'flash_responded' => 'تم حسم الطعن بنجاح.',
    'error_not_eligible' => 'لا يمكنك تقديم طعن على هذه المزايدة.',
    'error_already_filed' => 'لقد قدّمت طعنًا على هذه المزايدة بالفعل.',
    'error_invalid_transition' => 'لا يمكن تنفيذ هذا الإجراء على الطعن في حالته الحالية.',
    'error_invalid_decision' => 'قرار غير صالح.',
];
