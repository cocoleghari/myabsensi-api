<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            [
                // Identitas
                'name' => 'BPR Bank Surya Yudha',
                'legal_name' => 'PT BPR Suryayudha Kencana',
                'code' => 'BSY',
                'industry' => 'Perbankan',
                'logo' => null,
                'description' => 'Perusahaan yang bergerak di bidang Perbankan',

                // Legalitas
                'npwp' => '01.234.567.8-901.000',
                'nib' => '1234567890123',
                'established_date' => '2010-03-15',

                // Kontak & lokasi
                'email' => 'pusat@suryayudha.id',
                'phone' => '(0286) 591662',
                'fax' => '(0286) 591808',
                'website' => 'https://suryayudha.id/',
                'address' => 'Desa Rejasa, Kec. Madukara',
                'city' => 'Banjarnegara',
                'province' => 'Jawa Tengah',
                'postal_code' => '53482',
                'country' => 'Indonesia',

                // Konfigurasi HRIS
                'timezone' => 'Asia/Jakarta',
                'work_days' => '5',
                'default_clock_in' => '08:00:00',
                'default_clock_out' => '17:00:00',

                'is_active' => true,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('companies')->insert($companies);
    }
}
