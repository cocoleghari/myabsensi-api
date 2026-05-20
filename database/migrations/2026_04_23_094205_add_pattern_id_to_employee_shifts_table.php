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
        Schema::table('employee_shifts', function (Blueprint $table) {
            // Nullable: boleh pakai shift langsung ATAU pola mingguan
            $table->foreignId('pattern_id')
                ->nullable()
                ->after('shift_id')
                ->constrained('shift_weekly_patterns')
                ->nullOnDelete();

            // shift_id sekarang juga nullable karena bisa pakai pola saja
            $table->foreignId('shift_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_shifts', function (Blueprint $table) {
            //
        });
    }
};
