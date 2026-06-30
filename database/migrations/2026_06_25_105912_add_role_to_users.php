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
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin','admin','hrd','manager','supervisor','employee') NOT NULL DEFAULT 'employee'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('superadmin','admin','hrd','employee') NOT NULL DEFAULT 'employee'");
    }
};
