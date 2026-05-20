<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();

            $table->date('tanggal_mulai')->comment('Shift ini mulai berlaku');
            $table->date('tanggal_selesai')->nullable()->comment('Null = berlaku hingga diganti');

            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Index untuk query aktif
            $table->index(['employee_id', 'tanggal_mulai', 'tanggal_selesai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_shifts');
    }
};
