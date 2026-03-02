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
        Schema::create('pusat_lokasis', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lokasi');
            $table->string('titik_kordinat');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Index untuk pencarian
            $table->index('nama_lokasi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pusat_lokasis');
    }
};
