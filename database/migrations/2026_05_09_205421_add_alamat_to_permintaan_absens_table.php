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
        Schema::table('permintaan_absens', function (Blueprint $table) {
            $table->string('alamat_pengajuan')->nullable()
                ->after('jarak_meter')
                ->comment('Alamat/nama lokasi aktual saat pengajuan dari client');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permintaan_absens', function (Blueprint $table) {
            //
        });
    }
};
