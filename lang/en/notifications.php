<?php

return [
    'title' => 'Notifications',
    'unread' => ':count unread',
    'empty' => 'No notifications',
    'mark_read' => 'Mark as read',
    'mark_all_read' => 'Mark all as read',
    'just_now' => 'Just now',
    'flash_marked_read' => 'Notification marked as read.',
    'flash_all_marked_read' => 'All notifications marked as read.',

    'events' => [
        'auction_won' => [
            'title' => 'You won the auction',
            'body' => 'You won “:auction” for :amount. Pay within :days days.',
        ],
        'auction_lost' => [
            'title' => 'Auction ended',
            'body' => 'The auction “:auction” has ended. Your deposit will be refunded.',
        ],
        'payment_confirmed' => [
            'title' => 'Payment confirmed',
            'body' => 'Your :type payment of :amount for “:auction” is confirmed.',
        ],
        'payment_failed' => [
            'title' => 'Payment failed',
            'body' => 'Your :type payment for “:auction” failed.',
        ],
        'final_payment_due' => [
            'title' => 'Final payment due',
            'body' => 'Make the final payment for “:auction” within :days days.',
        ],
        'deposit_refunded' => [
            'title' => 'Deposit refunded',
            'body' => 'Your deposit of :amount for “:auction” has been refunded.',
        ],
        'deposit_forfeited' => [
            'title' => 'Deposit forfeited',
            'body' => 'Your deposit on “:auction” was forfeited for non-payment.',
        ],
        'outbid' => [
            'title' => 'You have been outbid',
            'body' => 'A higher bid (:amount) was placed on “:auction”.',
        ],
        'inspection_answered' => [
            'title' => 'Your question was answered',
            'body' => 'Your question about “:auction” has been answered.',
        ],
        'condition_book_published' => [
            'title' => 'Condition book published',
            'body' => 'The condition book for “:auction” is now available.',
        ],
        'delivery_update' => [
            'title' => 'Delivery update',
            'body' => 'Delivery status for “:auction”: :status.',
        ],
        'appeal_updated' => [
            'title' => 'Update on your appeal',
            'body' => 'Your appeal status: :status.',
        ],
        'appeal_submitted' => [
            'title' => 'New appeal',
            'body' => 'A new appeal has been filed on the auction “:auction”.',
        ],
        'appeal_forwarded' => [
            'title' => 'An appeal forwarded to you',
            'body' => 'An appeal on the auction “:auction” has been forwarded to you for a decision.',
        ],
        'appeal_entity_decided' => [
            'title' => 'Entity decision on an appeal',
            'body' => 'The entity issued its decision (:decision) on an appeal regarding the auction “:auction”.',
        ],
        'auction_report_referred' => [
            'title' => 'Auction report referred to you',
            'body' => 'A report on the auction “:auction” has been referred to you.',
        ],
    ],
];
