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
        Schema::create('permintaan_absens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('pusat_lokasi_id')->nullable()->constrained('pusat_lokasis')->nullOnDelete();

            $table->enum('tipe_absen', ['masuk', 'pulang']);
            $table->timestamp('waktu_pengajuan');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('jarak_meter', 8, 2)->nullable();

            $table->string('alasan');
            $table->text('keterangan')->nullable();
            $table->string('foto_path')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            // ← BARU: siapa yang harus approve
            $table->foreignId('approver_employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete()
                ->comment('Manager department karyawan saat pengajuan');

            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diproses_pada')->nullable();
            $table->text('catatan_admin')->nullable();

            $table->foreignId('absensi_id')->nullable()->constrained('absensis')->nullOnDelete();

            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['approver_employee_id', 'status']); // ← untuk query "permintaan yang harus saya approve"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permintaan_absens');
    }
};
