<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['code' => 'active',      'label' => 'Aktif',                'color' => 'green',  'is_active' => true,  'sort_order' => 1],
            ['code' => 'probation',   'label' => 'Masa Percobaan',       'color' => 'blue',   'is_active' => true,  'sort_order' => 2],
            ['code' => 'contract',    'label' => 'Kontrak',              'color' => 'cyan',   'is_active' => true,  'sort_order' => 3],
            ['code' => 'on_leave',    'label' => 'Cuti Panjang',         'color' => 'yellow', 'is_active' => false, 'sort_order' => 4],
            ['code' => 'inactive',    'label' => 'Tidak Aktif',          'color' => 'gray',   'is_active' => false, 'sort_order' => 5],
            ['code' => 'suspended',   'label' => 'Ditangguhkan',         'color' => 'orange', 'is_active' => false, 'sort_order' => 6],
            ['code' => 'terminated',  'label' => 'PHK',                  'color' => 'red',    'is_active' => false, 'sort_order' => 7],
            ['code' => 'resigned',    'label' => 'Mengundurkan Diri',    'color' => 'red',    'is_active' => false, 'sort_order' => 8],
            ['code' => 'retired',     'label' => 'Pensiun',              'color' => 'purple', 'is_active' => false, 'sort_order' => 9],
        ];

        DB::table('employee_statuses')->insert($statuses);
    }
}
