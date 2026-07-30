<?php

namespace App\Services\Push;

/**
 * A provider-agnostic push payload.
 *
 * `data` is the machine-readable half the app routes on (event type + deep-link
 * target); `title`/`body` are the already-localized display strings. Keeping the
 * two separate means a sender can deliver a data-only message on platforms where
 * the app renders its own notification.
 */
final class PushMessage
{
    /**
     * @param  array<string, string>  $data
     */
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {}

    /**
     * FCM requires every `data` value to be a string — cast here so callers can
     * pass ints/bools/nulls without each sender re-implementing the rule.
     *
     * @return array<string, string>
     */
    public function stringData(): array
    {
        $out = [];

        foreach ($this->data as $key => $value) {
            if ($value === null) {
                continue;
            }
            $out[$key] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }

        return $out;
    }
}
