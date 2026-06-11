<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Split the single `mother_fullname` column into `mother_name` +
     * `mother_surname` (spec: mother's first name and surname captured
     * separately during KYC).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mother_name')->nullable()->after('father_name');
            $table->string('mother_surname')->nullable()->after('mother_name');
        });

        // Best-effort backfill: split any existing full name on the first space.
        foreach (DB::table('users')->whereNotNull('mother_fullname')->select('id', 'mother_fullname')->get() as $row) {
            $full = trim($row->mother_fullname);
            if ($full === '') {
                continue;
            }
            $parts = preg_split('/\s+/', $full, 2);
            DB::table('users')->where('id', $row->id)->update([
                'mother_name' => $parts[0] ?? null,
                'mother_surname' => $parts[1] ?? null,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('mother_fullname');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mother_fullname')->nullable()->after('father_name');
        });

        foreach (DB::table('users')->select('id', 'mother_name', 'mother_surname')->get() as $row) {
            $full = trim(($row->mother_name ?? '').' '.($row->mother_surname ?? ''));
            if ($full !== '') {
                DB::table('users')->where('id', $row->id)->update(['mother_fullname' => $full]);
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mother_name', 'mother_surname']);
        });
    }
};
