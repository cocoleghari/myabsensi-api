<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanEmployeeAllFields extends Command
{
    protected $signature = 'employees:clean-all';

    protected $description = 'Bersihkan semua field string dari karakter non-UTF8';

    // Field string yang perlu dicek
    protected array $fields = [
        'full_name', 'nickname', 'employee_code', 'nik', 'ktp_number',
        'place_of_birth', 'address', 'city', 'province', 'bank_name',
        'bank_account_name', 'last_education_institution', 'last_education_major',
        'emergency_contact_name',
    ];

    public function handle()
    {
        $employees = DB::table('employees')->get(array_merge(['id', 'employee_code'], $this->fields));
        $totalFixed = 0;

        foreach ($employees as $e) {
            $updates = [];

            foreach ($this->fields as $field) {
                $original = $e->$field ?? '';
                if ($original === '') {
                    continue;
                }

                $clean = mb_convert_encoding($original, 'UTF-8', 'UTF-8');
                $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean ?? '');

                if ($clean !== $original) {
                    $updates[$field] = $clean;
                    $this->warn("  [{$e->id}] {$field}: corrupt ditemukan");
                }
            }

            if (! empty($updates)) {
                DB::table('employees')->where('id', $e->id)->update($updates);
                $this->info("Fixed [{$e->id}] {$e->employee_code} — ".implode(', ', array_keys($updates)));
                $totalFixed++;
            }
        }

        $this->info("Selesai. Total fixed: {$totalFixed} karyawan.");
    }
}
