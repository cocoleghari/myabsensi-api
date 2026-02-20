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
        Schema::create('absensis', function (Blueprint $table) {
  $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('lokasi_id')->constrained()->onDelete('cascade');
            $table->string('titik_koordinat_lokasi'); // Koordinat dari lokasi yang dipilih
            $table->string('titik_koordinat_kamu')->nullable(); // Koordinat real-time pengguna (boleh null)
            $table->timestamp('waktu_absen');
            $table->timestamps();
            
            // Index untuk performa
            $table->index(['user_id', 'waktu_absen']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
