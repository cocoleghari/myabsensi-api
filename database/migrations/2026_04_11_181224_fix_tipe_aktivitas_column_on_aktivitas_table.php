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
        Schema::table('aktivitas', function (Blueprint $table) {
            // ✅ Jadikan nullable agar tidak error saat insert
            $table->string('tipe_aktivitas')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('aktivitas', function (Blueprint $table) {
            $table->string('tipe_aktivitas')->nullable(false)->change();
        });
    }
};
