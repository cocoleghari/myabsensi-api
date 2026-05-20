<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            // Kode tingkat, misal: X_c, X_a, VI_c, VI_a
            $table->string('code')->comment('Kode tingkat jabatan, misal: X_c, VI_a');

            // Nama kelas lengkap
            $table->string('name')->comment('Nama kelas lengkap, misal: Wakil Kepala Wilayah - X c');

            // Urutan hierarki — semakin besar semakin tinggi
            $table->integer('grade')->comment('Urutan numerik hierarki grade');

            $table->text('description')->nullable();
            $table->integer('order')->default(0)->comment('Urutan tampil di UI');
            $table->boolean('is_active')->default(true);

            // Kode harus unik per company
            $table->unique(['company_id', 'code']);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_grades');
    }
};
