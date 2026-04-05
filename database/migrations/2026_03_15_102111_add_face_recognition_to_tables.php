<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('foto_wajah_path')->nullable()->after('email');
            $table->boolean('wajah_terdaftar')->default(false)->after('foto_wajah_path');
        });

        Schema::table('absensis', function (Blueprint $table) {
            $table->string('foto_absen_path')->nullable()->after('waktu_absen');
            $table->float('confidence_score')->nullable()->after('foto_absen_path');
            $table->boolean('wajah_cocok')->default(false)->after('confidence_score');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['foto_wajah_path', 'wajah_terdaftar']);
        });

        Schema::table('absensis', function (Blueprint $table) {
            $table->dropColumn(['foto_absen_path', 'confidence_score', 'wajah_cocok']);
        });
    }
};
