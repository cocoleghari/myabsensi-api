<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Cari karyawan di sekitar nama itu
$employees = DB::table('employees')
    ->select('id', 'employee_code', 'full_name', 'photo_url', 'department_id')
    ->orderBy('full_name')
    ->get();

// Encode satu per satu, cari yang bermasalah
foreach ($employees as $i => $e) {
    $arr = [
        'id' => $e->id,
        'employee_code' => $e->employee_code,
        'full_name' => $e->full_name,
        'photo_url' => $e->photo_url,
        'department_id' => $e->department_id,
    ];
    $json = json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

    // Cek apakah hasil encode mengandung karakter bermasalah
    if (strpos($json, '"photo_url"n') !== false ||
        strpos($json, '"photo_url":n') === false && strpos($json, '"photo_url":') === false) {
        echo "PROBLEM at index $i: id={$e->id} name={$e->full_name}".PHP_EOL;
        echo "JSON: $json".PHP_EOL;
        echo 'HEX full_name: '.bin2hex($e->full_name).PHP_EOL;
    }
}
echo 'Done'.PHP_EOL;
