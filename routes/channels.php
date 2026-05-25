<?php

use App\Models\Auction;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, string $id) {
    return $user->id === $id;
});

// Public auction channel — anyone can watch live bid prices.
Broadcast::channel('auction.{auctionId}', function ($user, string $auctionId) {
    return Auction::query()->whereKey($auctionId)->exists();
});

// Private confirmation channel for a participant on a specific auction.
Broadcast::channel('auction.{auctionId}.user.{userId}', function (User $user, string $auctionId, string $userId) {
    return $user->id === $userId
        && $user->participations()->where('auction_id', $auctionId)->exists();
});
