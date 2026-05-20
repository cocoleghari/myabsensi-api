<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            // Self-referencing untuk hierarki
            // null = departemen level teratas (tidak punya induk)
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete()
                ->comment('Null jika departemen level teratas');

            // Manajer departemen — nullable karena saat create belum tentu ada karyawan
            $table->unsignedBigInteger('manager_id')->nullable();

            $table->string('name');
            $table->string('code', 20)->nullable()->comment('Kode unik per company, misal: IT, HRD, FIN');
            $table->text('description')->nullable();
            $table->integer('order')->default(0)->comment('Urutan tampil di UI');
            $table->boolean('is_active')->default(true);

            // Kombinasi company_id + code harus unik
            // (kode IT boleh ada di company A dan company B, tapi tidak boleh duplikat dalam satu company)
            $table->unique(['company_id', 'code']);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
