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
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik', 5)->nullable()->after('id');
            $table->string('nama_stempel')->nullable()->after('name');
            $table->date('tgl_lahir')->nullable()->after('nama_stempel');
            $table->string('jabatan')->nullable()->after('tgl_lahir');
            $table->string('kantor')->nullable()->after('jabatan');
            $table->enum('jk', ['L', 'P'])->nullable()->after('kantor');
            $table->text('alamat')->nullable()->after('jk');
            $table->date('tgl_masuk')->nullable()->after('alamat');
            $table->string('nomor_telp', 20)->nullable()->after('tgl_masuk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nik',
                'nama_stempel',
                'tgl_lahir',
                'jabatan',
                'kantor',
                'jk',
                'alamat',
                'tgl_masuk',
                'nomor_telp',
            ]);
        });
    }
};
