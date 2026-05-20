<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('job_level_id')
                ->nullable()
                ->constrained('job_levels')
                ->nullOnDelete()
                ->comment('Level hierarki jabatan, misal: Staff, Manager');
            $table->foreignId('job_grade_id')
                ->nullable()
                ->constrained('job_grades')
                ->nullOnDelete()
                ->comment('Grade/kelas jabatan, misal: X_c, VI_a');

            // Identitas
            $table->string('employee_code')->nullable()->comment('Kode karyawan, misal EMP-001');
            $table->string('nik')->unique()->comment('Nomor Induk Karyawan internal');
            $table->string('ktp_number')->nullable()->unique()->comment('Nomor KTP');
            $table->string('full_name');
            $table->string('nickname')->nullable()->unique()->comment('Nama panggilan');
            $table->enum('gender', ['male', 'female']);
            $table->string('place_of_birth')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->string('religion')->nullable();
            $table->string('blood_type', 3)->nullable();
            $table->string('foto_wajah_path')->nullable();
            $table->boolean('wajah_terdaftar')->default(false);
            $table->string('photo_url')->nullable();

            // Kontak
            $table->string('phone')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();

            // Alamat
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 10)->nullable();

            // Kepegawaian
            $table->enum('employment_type', ['permanent', 'contract', 'intern', 'freelance'])->default('permanent');
            $table->date('join_date')->nullable();
            $table->date('contract_end_date')->nullable()->comment('Diisi jika kontrak/magang');
            $table->date('resign_date')->nullable();
            $table->foreignId('employee_status_id')->nullable()->constrained('employee_statuses')->nullOnDelete();

            // Data finansial & legal
            $table->string('npwp')->nullable()->unique();
            $table->string('bpjs_kesehatan')->nullable()->unique();
            $table->string('bpjs_ketenagakerjaan')->nullable()->unique();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();

            // Pendidikan terakhir
            $table->enum('last_education', ['sd', 'smp', 'sma', 'd1', 'd2', 'd3', 'd4', 's1', 's2', 's3'])->nullable();
            $table->string('last_education_major')->nullable();
            $table->string('last_education_institution')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
