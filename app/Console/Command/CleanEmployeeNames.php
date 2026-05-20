<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanEmployeeNames extends Command
{
    protected $signature = 'employees:clean-names';

    protected $description = 'Bersihkan karakter non-UTF8 dari nama karyawan';

    public function handle()
    {
        $employees = DB::table('employees')->get(['id', 'full_name', 'employee_code']);
        $fixed = 0;

        foreach ($employees as $e) {
            $original = $e->full_name ?? '';
            $clean = mb_convert_encoding($original, 'UTF-8', 'UTF-8');
            $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $clean ?? '');

            if ($clean !== $original) {
                DB::table('employees')->where('id', $e->id)->update(['full_name' => $clean]);
                $this->info("Fixed [{$e->id}] {$e->employee_code}: '{$original}' → '{$clean}'");
                $fixed++;
            }
        }

        $this->info("Selesai. Total fixed: {$fixed} karyawan.");
    }
}
