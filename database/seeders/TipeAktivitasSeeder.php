<?php

namespace Database\Seeders;

use App\Models\TipeAktivitas;
use Illuminate\Database\Seeder;

class TipeAktivitasSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['nama' => 'Dinas Luar',           'has_tujuan' => false, 'has_kendaraan' => false],
            ['nama' => 'Istirahat',             'has_tujuan' => false, 'has_kendaraan' => false],
            ['nama' => 'Marketing',             'has_tujuan' => true,  'has_kendaraan' => true],
            ['nama' => 'Meeting',               'has_tujuan' => false, 'has_kendaraan' => false],
            ['nama' => 'Mewakili BSY',          'has_tujuan' => true,  'has_kendaraan' => false],
            ['nama' => 'On The Spot',           'has_tujuan' => false, 'has_kendaraan' => false],
            ['nama' => 'Pelatihan/Pendidikan',  'has_tujuan' => false, 'has_kendaraan' => false],
            ['nama' => 'Penagihan',             'has_tujuan' => true,  'has_kendaraan' => true],
            ['nama' => 'Sidang',                'has_tujuan' => true,  'has_kendaraan' => true],
            ['nama' => 'Survey',                'has_tujuan' => true,  'has_kendaraan' => true],
            ['nama' => 'Visitasi',              'has_tujuan' => true,  'has_kendaraan' => true],
        ];

        foreach ($data as $item) {
            TipeAktivitas::create($item);
        }
    }
}
