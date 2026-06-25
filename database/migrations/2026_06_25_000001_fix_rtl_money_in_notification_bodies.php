<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill: existing in-app notification bodies embed the amount as plain text
 * (e.g. "بمبلغ 200 000 دج") built with dzd() before the RTL money fix. In an
 * Arabic sentence the bidi algorithm reverses the number's space groups
 * ("200 000" → "000 200"). New notifications use dzd_text() (which wraps the
 * amount in a U+2066…U+2069 LTR isolate); this migration applies the same isolate
 * to the already-stored rows so they read correctly too.
 *
 * Idempotent: only touches bodies that contain "دج" and are not already isolated.
 */
return new class extends Migration
{
    private const LRI = "\u{2066}"; // LEFT-TO-RIGHT ISOLATE
    private const PDI = "\u{2069}"; // POP DIRECTIONAL ISOLATE

    public function up(): void
    {
        DB::table('notifications')
            ->where('body', 'like', '%دج%')
            ->where('body', 'not like', '%'.self::LRI.'%')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    // Wrap the amount (digits with space/comma grouping) that sits
                    // right before "دج" in an LTR isolate so it stays coherent.
                    $fixed = preg_replace(
                        '/(\d[\d ,]*\d|\d)(\s?دج)/u',
                        self::LRI.'$1'.self::PDI.'$2',
                        $row->body,
                    );

                    if ($fixed !== null && $fixed !== $row->body) {
                        DB::table('notifications')
                            ->where('id', $row->id)
                            ->update(['body' => $fixed]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Strip the isolate controls we added (best-effort; safe to leave in place).
        DB::table('notifications')
            ->where('body', 'like', '%'.self::LRI.'%')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $reverted = str_replace([self::LRI, self::PDI], '', $row->body);
                    if ($reverted !== $row->body) {
                        DB::table('notifications')
                            ->where('id', $row->id)
                            ->update(['body' => $reverted]);
                    }
                }
            });
    }
};
