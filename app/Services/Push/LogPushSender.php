<?php

namespace App\Services\Push;

use Illuminate\Support\Facades\Log;

/**
 * Default (and test/local) sender: records what WOULD have been pushed and
 * delivers nothing. Active whenever no push provider is configured, so the whole
 * notification pipeline — device registration, channel selection, payload
 * building — can be exercised end to end without credentials.
 */
class LogPushSender implements PushSender
{
    public function send(array $tokens, PushMessage $message): array
    {
        Log::debug('Push (log driver — not delivered)', [
            'tokens' => count($tokens),
            'title' => $message->title,
            'data' => $message->stringData(),
        ]);

        return [];
    }
}
