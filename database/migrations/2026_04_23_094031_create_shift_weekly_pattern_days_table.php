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
        Schema::create('shift_weekly_pattern_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pattern_id')
                ->constrained('shift_weekly_patterns')
                ->cascadeOnDelete();
            // null = hari libur / off
            $table->foreignId('shift_id')
                ->nullable()
                ->constrained('shifts')
                ->nullOnDelete();
            // 0=Senin, 1=Selasa, ..., 6=Minggu
            $table->tinyInteger('hari')->unsigned();
            $table->boolean('is_libur')->default(false);
            $table->string('keterangan', 200)->nullable();
            $table->timestamps();

            $table->unique(['pattern_id', 'hari']); // 1 baris per hari per pola
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_weekly_pattern_days');
    }
};
