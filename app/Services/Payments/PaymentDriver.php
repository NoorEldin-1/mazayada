<?php

namespace App\Services\Payments;

/**
 * Single source of truth for which payment driver is active. Used by BOTH the
 * container binding (AppServiceProvider) and PaymentService::driverName() so the
 * resolved gateway and the value stored on payments.gateway never drift apart.
 *
 * Resolution: setting('payments.driver') → config('mazayada.payments.driver').
 * Safety net: 'chargily' falls back to 'mock' when no secret key is configured
 * (automated tests / fresh local installs) so we never hit the network without
 * credentials.
 */
final class PaymentDriver
{
    public const MOCK = 'mock';
    public const CHARGILY = 'chargily';
    public const CIBWEB = 'cibweb';

    public static function current(): string
    {
        $driver = (string) setting('payments.driver', config('mazayada.payments.driver', self::MOCK));

        // Chargily needs a secret key to reach the API; without one we'd fail
        // every charge, so fall back to the mock (keeps tests/local offline-safe).
        if ($driver === self::CHARGILY && blank(config('mazayada.payments.chargily.secret_key'))) {
            return self::MOCK;
        }

        return in_array($driver, [self::MOCK, self::CHARGILY, self::CIBWEB], true)
            ? $driver
            : self::MOCK;
    }

    public static function make(): PaymentGatewayInterface
    {
        return match (self::current()) {
            self::CHARGILY => new ChargilyGateway(),
            self::CIBWEB => new CibWebGateway(),
            default => new MockPaymentGateway(),
        };
    }
}
