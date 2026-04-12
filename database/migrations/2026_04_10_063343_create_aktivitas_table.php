<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('tugas');
            $table->datetime('mulai');
            $table->datetime('berakhir');
            $table->string('tipe_aktivitas');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('akurasi_meter', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('aktivitas_foto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aktivitas_id')->constrained('aktivitas')->onDelete('cascade');
            $table->string('foto_path');
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aktivitas_foto');
        Schema::dropIfExists('aktivitas');
    }
};
