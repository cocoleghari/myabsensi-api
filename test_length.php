<?php

// test_length.php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$employees = DB::table('employees')
    ->select('id', 'employee_code', 'full_name', 'photo_url', 'department_id')
    ->orderBy('full_name')
    ->get()
    ->toArray();

$json = json_encode(
    ['data' => $employees],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
);

echo 'strlen (bytes): '.strlen($json).PHP_EOL;
echo 'mb_strlen (chars): '.mb_strlen($json, 'UTF-8').PHP_EOL;
echo 'Difference: '.(strlen($json) - mb_strlen($json, 'UTF-8')).PHP_EOL;

// Cari semua multibyte chars
$multibyte = [];
for ($i = 0; $i < mb_strlen($json, 'UTF-8'); $i++) {
    $char = mb_substr($json, $i, 1, 'UTF-8');
    $byteLen = strlen($char);
    if ($byteLen > 1) {
        $multibyte[] = [
            'char' => $char,
            'bytes' => $byteLen,
            'hex' => bin2hex($char),
            'pos_bytes' => null, // akan dihitung
        ];
    }
}

echo 'Total multibyte chars: '.count($multibyte).PHP_EOL;
foreach (array_slice($multibyte, 0, 20) as $m) {
    echo "  char='{$m['char']}' bytes={$m['bytes']} hex={$m['hex']}".PHP_EOL;
}

// Cari konteks multibyte char
preg_match_all('/[^\x00-\x7F]+/', $json, $matches, PREG_OFFSET_CAPTURE);
echo PHP_EOL.'Non-ASCII sequences:'.PHP_EOL;
foreach ($matches[0] as $m) {
    $pos = $m[1];
    $val = $m[0];
    echo "  pos=$pos hex=".bin2hex($val).' context='.substr($json, max(0, $pos - 30), 60).PHP_EOL;
}
