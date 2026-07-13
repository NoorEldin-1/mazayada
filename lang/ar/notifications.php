<?php

return [
    'title' => 'الإشعارات',
    'unread' => ':count غير مقروءة',
    'empty' => 'لا توجد إشعارات',
    'mark_read' => 'تعليم كمقروء',
    'mark_all_read' => 'تعليم الكل كمقروء',
    'just_now' => 'الآن',
    'flash_marked_read' => 'تم تعليم الإشعار كمقروء.',
    'flash_all_marked_read' => 'تم تعليم جميع الإشعارات كمقروءة.',

    // In-app copy for the auction lifecycle events (spec §10.1).
    'events' => [
        'auction_won' => [
            'title' => 'لقد فزت بالمزايدة',
            'body' => 'فزت بمزايدة «:auction» بمبلغ :amount. أتمم الدفع خلال :days يوماً.',
        ],
        'auction_lost' => [
            'title' => 'انتهت المزايدة',
            'body' => 'انتهت مزايدة «:auction» ولم تفز بها. سيُعاد التأمين تلقائياً.',
        ],
        'payment_confirmed' => [
            'title' => 'تم تأكيد الدفع',
            'body' => 'تم تأكيد دفعتك (:type) بمبلغ :amount لمزايدة «:auction».',
        ],
        'payment_failed' => [
            'title' => 'فشل الدفع',
            'body' => 'تعذّر إتمام دفعتك (:type) لمزايدة «:auction».',
        ],
        'final_payment_due' => [
            'title' => 'الدفع النهائي مستحق',
            'body' => 'أتمم الدفع النهائي لمزايدة «:auction» خلال :days يوماً.',
        ],
        'deposit_refunded' => [
            'title' => 'تم استرداد الكفالة',
            'body' => 'تم استرداد كفالتك بمبلغ :amount عن مزايدة «:auction».',
        ],
        'deposit_forfeited' => [
            'title' => 'تم مصادرة الكفالة',
            'body' => 'صودرت كفالتك في مزايدة «:auction» لعدم إتمام الدفع.',
        ],
        'outbid' => [
            'title' => 'تم تجاوز عرضك',
            'body' => 'قُدِّم عرض أعلى (:amount) في مزايدة «:auction».',
        ],
        'inspection_answered' => [
            'title' => 'تمت الإجابة على سؤالك',
            'body' => 'تمت الإجابة على سؤالك بخصوص مزايدة «:auction».',
        ],
        'condition_book_published' => [
            'title' => 'نُشرت كراسة الشروط',
            'body' => 'كراسة شروط مزايدة «:auction» متاحة الآن.',
        ],
        'delivery_update' => [
            'title' => 'تحديث التسليم',
            'body' => 'حالة تسليم مزايدة «:auction»: :status.',
        ],
        'appeal_updated' => [
            'title' => 'تحديث على طعنك',
            'body' => 'حالة طعنك الآن: :status.',
        ],
        'appeal_submitted' => [
            'title' => 'طعن جديد',
            'body' => 'تم تقديم طعن جديد على مزايدة «:auction».',
        ],
        'appeal_forwarded' => [
            'title' => 'طعن محال إليكم',
            'body' => 'تمت إحالة طعن على مزايدة «:auction» إليكم للبتّ فيه.',
        ],
        'appeal_entity_decided' => [
            'title' => 'قرار الجهة على طعن',
            'body' => 'أصدرت الجهة قرارها (:decision) بشأن طعن على مزايدة «:auction».',
        ],
        'auction_report_referred' => [
            'title' => 'تقرير مزاد محال إليكم',
            'body' => 'تمت إحالة تقرير عن مزايدة «:auction» إليكم.',
        ],
    ],
];
