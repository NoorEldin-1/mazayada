<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // An institutional account — a government entity's own login — is not
            // a person: it has no national ID, phone or date of birth. Relax these
            // columns so a non-person account can exist. Person accounts (citizens
            // and individual staff) still require them at the form layer. The
            // unique indexes stay (MySQL permits multiple NULLs in a unique index).
            $table->string('nin', 18)->nullable()->change();
            $table->string('phone', 10)->nullable()->change();
            $table->date('birth_date')->nullable()->change();

            // PERSON  = citizen or individual staff member (default for all rows).
            // INSTITUTION = a government entity's own read-only login account.
            $table->string('account_type', 20)->default('PERSON')->after('role');
            $table->index('account_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['account_type']);
            $table->dropColumn('account_type');
            // Nullability is intentionally not reverted: institutional rows created
            // while this migration was applied could hold NULLs that would block it.
        });
    }
};
