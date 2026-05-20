<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            // Identitas perusahaan
            $table->string('name')->comment('Nama tampilan perusahaan');
            $table->string('legal_name')->nullable()->comment('Nama resmi sesuai akta');
            $table->string('code')->unique()->comment('Kode singkat, misal: PT-ABC');
            $table->string('industry')->nullable()->comment('Bidang usaha');
            $table->string('logo')->nullable();
            $table->text('description')->nullable();

            // Legalitas
            $table->string('npwp')->nullable()->unique();
            $table->string('nib')->nullable()->unique()->comment('Nomor Induk Berusaha');
            $table->date('established_date')->nullable();

            // Kontak & lokasi
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('country')->default('Indonesia');

            // Konfigurasi HRIS
            $table->string('timezone')->default('Asia/Jakarta');
            $table->enum('work_days', ['5', '6'])->default('5')->comment('Jumlah hari kerja per minggu');
            $table->time('default_clock_in')->default('08:00:00');
            $table->time('default_clock_out')->default('17:00:00');

            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
