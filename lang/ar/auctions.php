<?php

return [
    // ===== Auction card / listing labels (reused on landing + listings) =====
    'live' => 'مباشر',
    'coming_soon' => 'قريباً',
    'current_price' => 'السعر الحالي',
    'starting_price' => 'السعر الابتدائي',
    'bids_word' => 'عرض',
    'general_category' => 'عام',
    'upcoming_title' => 'مزايدة قادمة قريباً',
    'default_location' => 'الجزائر',

    // ===== Bidding service errors (thrown from App\Services\BiddingService) =====
    'bid' => [
        'invalid_amount' => 'المبلغ غير صالح.',
        'not_eligible' => 'لا تستوفي شروط المزايدة (KYC أو الحالة).',
        'rate_limited' => 'تجاوزت الحد الأقصى للمزايدات (:max في الدقيقة).',
        'not_active' => 'المزايدة ليست نشطة حالياً.',
        'ended' => 'انتهت مدة المزايدة.',
        'must_register' => 'يجب التسجيل ودفع الكفالة أولاً.',
        'too_low' => 'المبلغ يجب أن يكون أعلى من السعر الحالي.',
        'failed' => 'فشل في تسجيل المزايدة، حاول مرة أخرى.',
    ],

    // ===== Public listing (auctions/index) =====
    'browse' => [
        'total_prefix' => 'إجمالي',
        'total_suffix' => 'مزايدة متاحة',
        'filter_category' => 'الفئة',
        'filter_wilaya' => 'الولاية',
        'filter_status' => 'الحالة',
        'filter_type' => 'النوع',
        'none_title' => 'لا توجد مزايدات',
        'none_desc' => 'لم يتم العثور على أي مزايدات تطابق معايير البحث الخاصة بك.',
    ],

    // ===== Auction detail (auctions/show) =====
    'show' => [
        'back' => 'العودة إلى المزايدات',
        'tab_details' => 'التفاصيل',
        'tab_specs' => 'المواصفات',
        'tab_bids' => 'سجل العروض',
        'desc_title' => 'وصف المزايدة',
        'no_desc' => 'لا يوجد وصف متاح لهذه المزايدة.',
        'spec_opening' => 'سعر الافتتاح',
        'spec_deposit' => 'التأمين',
        'spec_entry' => 'رسوم الدخول',
        'spec_book' => 'سعر الدفتر',
        'spec_units' => 'عدد الوحدات',
        'spec_wilaya' => 'الولاية',
        'spec_condition' => 'الحالة',
        'spec_type' => 'النوع',
        'recent_prefix' => 'آخر',
        'th_bidder' => 'المُزايد',
        'th_amount' => 'المبلغ',
        'th_time' => 'الوقت',
        'no_bids' => 'لا توجد عروض حتى الآن',
        'bids_so_far' => 'عرض حتى الآن',
        'cd_hours' => 'ساعة',
        'cd_minutes' => 'دقيقة',
        'cd_seconds' => 'ثانية',
        'login_to_participate' => 'سجّل الدخول للمشاركة',
        'register_in' => 'سجّل في هذه المزايدة',
        'amount_placeholder' => 'المبلغ بالسنتيم',
        'place_bid' => 'قدّم عرضك',
        'closed' => 'المزايدة مغلقة',
        'winner_label' => 'الفائز:',
        'no_winner' => 'لم يتم تحديد فائز',
        'not_started' => 'المزايدة لم تبدأ بعد',
        'recent_bids' => 'آخر العروض',
        'no_bids_side' => 'لا توجد عروض بعد',
    ],

    // ===== Controller flash messages =====
    'flash_registered' => 'تم التسجيل في المزاد بنجاح.',
    'flash_already_registered' => 'أنت مسجل بالفعل في هذا المزاد.',
    'flash_bid_placed' => 'تم تقديم عرضك بنجاح.',
    'bid_too_low_priced' => 'يجب أن يكون المبلغ أعلى من السعر الحالي (:price).',
];
