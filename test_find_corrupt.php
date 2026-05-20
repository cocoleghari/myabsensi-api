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

echo 'Total length: '.strlen($json).PHP_EOL;

// Cari semua posisi dimana "photo_url: (tanpa kutip penutup)
$pattern = '"photo_url:';
$offset = 0;
$found = 0;
while (($pos = strpos($json, $pattern, $offset)) !== false) {
    echo "CORRUPT at pos $pos:".PHP_EOL;
    echo substr($json, max(0, $pos - 50), 100).PHP_EOL;
    echo 'HEX sekitar pos: '.bin2hex(substr($json, max(0, $pos - 5), 20)).PHP_EOL;
    $offset = $pos + 1;
    $found++;
}

echo PHP_EOL."Total corrupt photo_url: $found".PHP_EOL;

// Juga cek pattern field lain yang mungkin corrupt
foreach (['full_name', 'employee_code', 'department_id'] as $field) {
    $pat = '"'.$field.':'; // tanpa kutip penutup = corrupt
    if (strpos($json, $pat) !== false) {
        echo "CORRUPT field: $field".PHP_EOL;
    }
}
