<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan_absens', function (Blueprint $table) {
            $table->enum('jenis', ['izin_lokasi', 'koreksi_lupa_masuk'])
                ->default('izin_lokasi')
                ->after('tipe_absen')
                ->comment('izin_lokasi = absen di luar radius, koreksi_lupa_masuk = lupa absen masuk');

            $table->timestamp('waktu_koreksi')->nullable()
                ->after('waktu_pengajuan')
                ->comment('Waktu absen masuk yang diklaim karyawan (untuk koreksi)');
        });
    }

    public function down(): void
    {
        Schema::table('permintaan_absens', function (Blueprint $table) {
            $table->dropColumn(['jenis', 'waktu_koreksi']);
        });
    }
};
