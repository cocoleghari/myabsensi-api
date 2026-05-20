<?php

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$employees = DB::table('employees')
    ->select('id', 'employee_code', 'full_name', 'photo_url', 'department_id')
    ->orderBy('full_name')
    ->get();

$json = json_encode(
    ['data' => $employees->toArray()],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
);

$error = json_last_error();
echo 'JSON valid: '.($error === JSON_ERROR_NONE ? 'YES' : 'NO - '.json_last_error_msg()).PHP_EOL;
echo 'Length: '.strlen($json).PHP_EOL;

if ($error !== JSON_ERROR_NONE) {
    // Cari posisi error dengan encode per item
    foreach ($employees as $emp) {
        $test = json_encode($emp);
        if ($test === false) {
            echo 'CORRUPT: id='.$emp->id.' code='.$emp->employee_code.PHP_EOL;
        }
    }
}
