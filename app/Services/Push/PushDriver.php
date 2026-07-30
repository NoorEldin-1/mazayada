<?php

namespace App\Services\Push;

/**
 * Single source of truth for the active push transport — the same pattern as
 * PaymentDriver.
 *
 * Resolution: config('mazayada.push.driver'). Safety net: 'fcm' falls back to
 * 'log' when no service-account file is configured, so tests and fresh installs
 * never attempt a network call.
 */
final class PushDriver
{
    public const LOG = 'log';

    public const FCM = 'fcm';

    public static function current(): string
    {
        $driver = (string) config('mazayada.push.driver', self::LOG);

        if ($driver === self::FCM && blank(config('mazayada.push.fcm.credentials'))) {
            return self::LOG;
        }

        return in_array($driver, [self::LOG, self::FCM], true) ? $driver : self::LOG;
    }

    /** Whether push delivery is actually wired to a provider. */
    public static function isEnabled(): bool
    {
        return self::current() !== self::LOG;
    }

    public static function make(): PushSender
    {
        return match (self::current()) {
            self::FCM => new FcmPushSender(),
            default => new LogPushSender(),
        };
    }
}
