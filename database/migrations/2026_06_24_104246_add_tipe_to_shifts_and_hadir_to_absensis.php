<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom tipe di shifts
        Schema::table('shifts', function (Blueprint $table) {
            $table->enum('tipe', ['reguler', 'flex'])
                ->default('reguler')
                ->after('kode')
                ->comment('reguler = jam masuk/pulang kaku, flex = tanpa jam kaku (satpam/IT)');
        });

        // Tambah nilai hadir di ENUM status absensis
        DB::statement("ALTER TABLE absensis MODIFY COLUMN status ENUM('tepat_waktu','terlambat','lembur','hadir') NOT NULL DEFAULT 'tepat_waktu'");
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('tipe');
        });

        DB::statement("ALTER TABLE absensis MODIFY COLUMN status ENUM('tepat_waktu','terlambat','lembur') NOT NULL DEFAULT 'tepat_waktu'");
    }
};
