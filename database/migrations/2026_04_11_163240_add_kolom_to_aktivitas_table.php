<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('aktivitas', function (Blueprint $table) {
            $table->unsignedBigInteger('tipe_aktivitas_id')->nullable()->after('tipe_aktivitas');
            $table->string('tujuan')->nullable()->after('tipe_aktivitas_id');
            $table->string('kendaraan_nopol')->nullable()->after('tujuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aktivitas', function (Blueprint $table) {
            //
        });
    }
};
