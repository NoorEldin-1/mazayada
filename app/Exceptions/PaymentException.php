<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A payment/registration eligibility refusal that carries a STABLE machine code
 * alongside the localized message.
 *
 * Most of these are not errors — they are "you are already past this step" or
 * "do X first" states that the client must branch on. Matching the translated
 * message string was the only option before; the `errorCode` here is the
 * contract the mobile app keys off instead (surfaced as `code` in the JSON
 * error envelope — see RespondsWithEnvelope::fail).
 *
 * Extends RuntimeException so the existing web controllers, which catch
 * RuntimeException and flash getMessage(), keep working untouched.
 */
class PaymentException extends RuntimeException
{
    /** @param string $errorCode stable, snake_case, never translated */
    public function __construct(string $message, public readonly string $errorCode)
    {
        parent::__construct($message);
    }

    /** The user is not verified / is suspended, so cannot take part at all. */
    public static function notEligible(): self
    {
        return new self(__('payments.not_eligible'), 'not_eligible');
    }

    /** The condition book must be bought before registering. */
    public static function mustPurchaseBook(): self
    {
        return new self(__('payments.must_purchase_book'), 'must_purchase_book');
    }

    /** The auction is Commercial-Register gated and the user holds none. */
    public static function commerceRegisterRequired(): self
    {
        return new self(__('payments.commerce_register_required'), 'commerce_register_required');
    }

    /** Already fully registered — the client should go straight to bidding. */
    public static function alreadyRegistered(): self
    {
        return new self(__('payments.already_registered'), 'already_registered');
    }

    /** Nothing to charge (no deposit configured). */
    public static function nothingDue(): self
    {
        return new self(__('payments.nothing_due'), 'nothing_due');
    }

    /** The book is free — no purchase step exists. */
    public static function bookFree(): self
    {
        return new self(__('payments.book_free'), 'book_free');
    }

    /** The book is already owned — the client should move on to registration. */
    public static function alreadyBoughtBook(): self
    {
        return new self(__('payments.already_bought_book'), 'already_bought_book');
    }

    /** The auction's time is over (or it is closed/cancelled) — the book is no longer sold (edit 3). */
    public static function bookSalesClosed(): self
    {
        return new self(__('payments.book_sales_closed'), 'book_sales_closed');
    }

    /** Platform / entity staff never take part in auctions (edit 4). */
    public static function staffNotAllowed(): self
    {
        return new self(__('payments.staff_not_allowed'), 'staff_not_allowed');
    }

    /** No active subscription plan with that code (edit 24). */
    public static function planUnavailable(): self
    {
        return new self(__('payments.plan_unavailable'), 'plan_unavailable');
    }

    /** Final payment is the winner's step only. */
    public static function notWinner(): self
    {
        return new self(__('payments.not_winner'), 'not_winner');
    }

    /** The final payment is already confirmed. */
    public static function finalAlreadyPaid(): self
    {
        return new self(__('payments.final_already_paid'), 'final_already_paid');
    }

    /** The gateway itself refused / is unreachable. */
    public static function gatewayError(): self
    {
        return new self(__('payments.gateway_error'), 'gateway_error');
    }
}
