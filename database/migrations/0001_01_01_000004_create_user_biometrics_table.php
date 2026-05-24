<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_biometrics', function (Blueprint $table) {
            $table->uuid('user_id')->primary();
            $table->string('photo_biometric_path')->nullable();
            $table->string('selfie_with_id_path')->nullable();
            $table->string('id_front_path')->nullable();
            $table->string('id_back_path')->nullable();
            $table->uuid('kyc_verified_by')->nullable();
            $table->timestamp('kyc_verified_at')->nullable();
            $table->float('liveness_score')->nullable();
            $table->float('match_score')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_biometrics');
    }
};
