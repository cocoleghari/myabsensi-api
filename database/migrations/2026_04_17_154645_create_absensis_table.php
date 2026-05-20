<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('pusat_lokasi_id')->nullable()->constrained('pusat_lokasis')->nullOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();

            // Tanggal logis absensi (bukan tanggal waktu absen)
            // Misal: shift malam mulai Senin jam 22:00, karyawan pulang Selasa jam 01:00
            // tanggal_absen tetap diisi Senin agar laporan harian akurat
            $table->date('tanggal_absen')->comment('Tanggal logis absensi (bukan tanggal fisik waktu absen)');

            $table->enum('tipe_absen', ['masuk', 'pulang'])->default('masuk');
            $table->timestamp('waktu_absen');

            // Koordinat dipisah untuk kalkulasi jarak di DB (fungsi ST_Distance / Haversine)
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('jarak_meter', 8, 2)->nullable()->comment('Jarak karyawan ke pusat lokasi saat absen');

            // Face recognition
            $table->string('foto_absen_path')->nullable();
            $table->float('confidence_score')->nullable();
            $table->boolean('wajah_cocok')->default(false);

            // Status hasil kalkulasi
            $table->enum('status', ['tepat_waktu', 'terlambat', 'diluar_lokasi', 'lembur'])->nullable();
            $table->integer('menit_terlambat')->default(0);
            $table->integer('menit_lembur')->default(0)->comment('Dihitung saat absen pulang');

            $table->text('catatan')->nullable();
            $table->timestamps();

            // Index performa
            $table->index(['employee_id', 'tanggal_absen']);
            $table->index(['employee_id', 'tipe_absen', 'tanggal_absen']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
