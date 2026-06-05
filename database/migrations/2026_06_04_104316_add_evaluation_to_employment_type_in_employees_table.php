<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE employees MODIFY COLUMN employment_type ENUM('permanent', 'contract', 'intern', 'freelance', 'evaluation') DEFAULT 'permanent'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pastikan tidak ada data 'evaluation' sebelum rollback
        DB::statement("UPDATE employees SET employment_type = 'permanent' WHERE employment_type = 'evaluation'");
        DB::statement("ALTER TABLE employees MODIFY COLUMN employment_type ENUM('permanent', 'contract', 'intern', 'freelance') DEFAULT 'permanent'");
    }
};
