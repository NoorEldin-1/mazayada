<?php

namespace Database\Seeders;

use App\Models\Commune;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the complete Algerian commune list — all 58 wilayas, 1541 communes
 * (spec §3.4). Data lives in database/data/communes.json (sourced from the ONS
 * geographic code, 58-wilaya scheme). The `code` is synthesised as the 2-digit
 * wilaya code + a 3-digit sequence within that wilaya.
 *
 * Idempotent: clears the table and reloads, so re-running keeps the list exact
 * without duplicates.
 */
class CommuneSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/communes.json');

        if (! is_file($path)) {
            $this->command?->warn("communes.json not found at {$path} — skipping commune seeding.");

            return;
        }

        /** @var array<int, array{w:int, ar:string, fr:string}> $rows */
        $rows = json_decode(file_get_contents($path), true) ?: [];

        // Idempotent + cheap on repeat deploys: if the table already holds the
        // full list, do nothing — avoids re-truncating (which would needlessly
        // remap auto-increment IDs every deploy). Self-heals if incomplete.
        if (count($rows) > 0 && Commune::count() === count($rows)) {
            $this->command?->info('Communes already up to date ('.count($rows).').');

            return;
        }

        Schema::disableForeignKeyConstraints();
        DB::table('communes')->truncate();
        Schema::enableForeignKeyConstraints();

        $seqByWilaya = [];
        $batch = [];

        foreach ($rows as $row) {
            $w = (int) $row['w'];
            $seq = ($seqByWilaya[$w] = ($seqByWilaya[$w] ?? 0) + 1);

            $batch[] = [
                'wilaya_id' => $w,
                'code' => sprintf('%02d%03d', $w, $seq),
                'name_ar' => $row['ar'],
                'name_fr' => $row['fr'],
                'postal_code' => null,
            ];

            // Bulk-insert in chunks to keep memory and query size sane.
            if (count($batch) === 200) {
                Commune::insert($batch);
                $batch = [];
            }
        }

        if ($batch) {
            Commune::insert($batch);
        }

        $this->command?->info('Seeded '.count($rows).' communes across all wilayas.');
    }
}
