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
        Schema::create('employee_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Kode unik status, misal: active, probation, resign');
            $table->string('label')->comment('Label tampilan, misal: Aktif, Masa Percobaan, Resign');
            $table->string('color')->default('gray')->comment('Nama warna badge di UI, misal: green, red, gray');
            $table->boolean('is_active')->default(true)->comment('Apakah status ini mengizinkan absen & cuti');
            $table->boolean('is_visible')->default(true)->comment('Apakah tampil di dropdown pilihan');
            $table->integer('sort_order')->default(0)->comment('Urutan tampil di list');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_statuses');
    }
};
