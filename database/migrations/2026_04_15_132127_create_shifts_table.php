<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            $table->string('nama');
            $table->string('kode')->nullable()->comment('Misal: PAGI, SIANG, MALAM');

            // Jam kerja inti
            $table->time('jam_masuk')->comment('Jam masuk kerja, misal: 07:30');
            $table->time('jam_pulang')->comment('Jam pulang kerja, misal: 17:00');

            // Window toleransi clock-in (berapa menit sebelum/sesudah jam masuk masih bisa absen masuk)
            $table->integer('toleransi_terlambat_menit')->default(15)->comment('Menit toleransi keterlambatan sebelum dihitung terlambat');
            $table->integer('window_masuk_awal_menit')->default(60)->comment('Berapa menit sebelum jam masuk tombol absen sudah bisa ditekan');

            // Logika lintas hari (overnight shift)
            // Jika shift malam jam 22:00 - 06:00, maka pulang melewati tengah malam
            $table->boolean('melewati_tengah_malam')->default(false)->comment('true jika jam pulang melewati hari berikutnya');

            // Batas maksimal jam pulang — termasuk lembur
            // Misal shift pagi jam 07:30-17:00, lembur max sampai 01:00 dini hari berikutnya
            // Sistem akan reset tombol absen jika sudah melewati batas ini
            $table->time('batas_waktu_pulang')->comment('Batas akhir waktu pulang termasuk lembur. Setelah waktu ini, hari dianggap berganti.');

            // Apakah shift ini berlaku hari libur/minggu
            $table->boolean('berlaku_hari_libur')->default(false);
            $table->boolean('berlaku_akhir_pekan')->default(false);

            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);

            $table->unique(['company_id', 'kode']);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
