<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel pivot many-to-many antara employees dan pusat_lokasis.
     * Menggantikan tabel lokasis yang lama.
     *
     * Satu karyawan bisa punya beberapa pusat lokasi absensi (misal: kantor pusat + kantor cabang).
     * Satu pusat lokasi bisa dipakai oleh banyak karyawan.
     * Setiap relasi memiliki radius_meter sendiri agar fleksibel per-karyawan.
     */
    public function up(): void
    {
        Schema::create('employee_pusat_lokasi', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->foreignId('pusat_lokasi_id')
                ->constrained('pusat_lokasis')
                ->cascadeOnDelete();

            $table->integer('radius_meter')
                ->default(100)
                ->comment('Radius toleransi absensi dalam meter untuk karyawan ini di lokasi ini');

            $table->text('keterangan')->nullable();

            $table->timestamps();

            // Satu karyawan tidak boleh didaftarkan dua kali ke pusat lokasi yang sama
            $table->unique(['employee_id', 'pusat_lokasi_id']);

            // Index performa untuk query absensi
            $table->index('employee_id');
            $table->index('pusat_lokasi_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_pusat_lokasi');
    }
};
