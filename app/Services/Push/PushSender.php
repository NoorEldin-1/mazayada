<?php

namespace App\Services\Push;

/**
 * Transport for a push notification. Implementations must be non-throwing: a
 * push provider being down or misconfigured must never break the request that
 * triggered the notification (a bid, a payment confirmation).
 */
interface PushSender
{
    /**
     * Deliver one message to a set of device tokens.
     *
     * @param  array<int, string>  $tokens
     * @return array<int, string>  tokens the provider reported as permanently
     *                             invalid, so the caller can prune them
     */
    public function send(array $tokens, PushMessage $message): array;
}
