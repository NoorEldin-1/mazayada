<?php

return [
    // ===== Auction card / listing labels (reused on landing + listings) =====
    'live' => 'Live',
    'coming_soon' => 'Coming soon',
    'current_price' => 'Current price',
    'starting_price' => 'Starting price',
    'bids_word' => 'bids',
    'general_category' => 'General',
    'upcoming_title' => 'Auction coming soon',
    'default_location' => 'Algeria',

    // ===== Bidding service errors (thrown from App\Services\BiddingService) =====
    'bid' => [
        'invalid_amount' => 'Invalid amount.',
        'not_eligible' => 'You do not meet the auction requirements (KYC or status).',
        'rate_limited' => 'You have exceeded the bidding limit (:max per minute).',
        'not_active' => 'The auction is not currently active.',
        'ended' => 'The auction has ended.',
        'must_register' => 'You must register and pay the deposit first.',
        'too_low' => 'The amount must be higher than the current price.',
        'failed' => 'Failed to record the bid, please try again.',
    ],

    // ===== Public listing (auctions/index) =====
    'browse' => [
        'total_prefix' => 'Total',
        'total_suffix' => 'auctions available',
        'filter_category' => 'Category',
        'filter_wilaya' => 'Wilaya',
        'filter_status' => 'Status',
        'filter_type' => 'Type',
        'none_title' => 'No auctions',
        'none_desc' => 'No auctions were found matching your search criteria.',
    ],

    // ===== Auction detail (auctions/show) =====
    'show' => [
        'back' => 'Back to auctions',
        'tab_details' => 'Details',
        'tab_specs' => 'Specifications',
        'tab_bids' => 'Bid history',
        'desc_title' => 'Auction description',
        'no_desc' => 'No description available for this auction.',
        'spec_opening' => 'Opening price',
        'spec_deposit' => 'Deposit',
        'spec_entry' => 'Entry fee',
        'spec_book' => 'Booklet price',
        'spec_units' => 'Unit count',
        'spec_wilaya' => 'Wilaya',
        'spec_condition' => 'Condition',
        'spec_type' => 'Type',
        'recent_prefix' => 'Last',
        'th_bidder' => 'Bidder',
        'th_amount' => 'Amount',
        'th_time' => 'Time',
        'no_bids' => 'No bids yet',
        'bids_so_far' => 'bids so far',
        'cd_hours' => 'hours',
        'cd_minutes' => 'minutes',
        'cd_seconds' => 'seconds',
        'login_to_participate' => 'Log in to participate',
        'register_in' => 'Register for this auction',
        'cta_blocked' => 'You cannot participate — your account is blacklisted.',
        'cta_locked' => 'Your account is temporarily locked, please try again later.',
        'cta_complete_kyc' => 'Complete identity verification to participate',
        'cta_inactive' => 'Your account is not active for participation right now.',
        'amount_placeholder' => 'Amount in centimes',
        'place_bid' => 'Place your bid',
        'closed' => 'Auction closed',
        'winner_label' => 'Winner:',
        'no_winner' => 'No winner determined',
        'not_started' => 'The auction has not started yet',
        'recent_bids' => 'Recent bids',
        'no_bids_side' => 'No bids yet',
    ],

    // ===== Controller flash messages =====
    'flash_registered' => 'Successfully registered for the auction.',
    'flash_already_registered' => 'You are already registered for this auction.',
    'flash_bid_placed' => 'Your bid has been placed successfully.',
    'bid_too_low_priced' => 'The amount must be higher than the current price (:price).',
];
