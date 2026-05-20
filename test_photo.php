<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$employees = DB::table('employees')
    ->select('id', 'employee_code', 'photo_url')
    ->whereNotNull('photo_url')
    ->get();

$found = 0;
foreach ($employees as $e) {
    $json = json_encode(['photo_url' => $e->photo_url]);
    if ($json === false) {
        echo "CORRUPT photo_url - id:{$e->id} code:{$e->employee_code}".PHP_EOL;
        echo 'HEX: '.bin2hex($e->photo_url).PHP_EOL;
        $found++;
    }
}

// Cek juga yang NULL tapi ada karakter tersembunyi
$all = DB::table('employees')->select('id', 'employee_code', 'photo_url')->get();
foreach ($all as $e) {
    $val = $e->photo_url ?? '';
    $json = json_encode($val);
    if ($json === false) {
        echo "CORRUPT (null check) - id:{$e->id} code:{$e->employee_code}".PHP_EOL;
        $found++;
    }
}

echo "Total corrupt: $found".PHP_EOL;
