<?php

// test_binary.php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$employees = DB::table('employees')
    ->select('id', 'employee_code', 'full_name', 'photo_url', 'department_id')
    ->orderBy('full_name')
    ->get()
    ->toArray();

$total = count($employees);
echo "Total: $total".PHP_EOL;

function testChunk($chunk, $label)
{
    $json = json_encode(
        ['data' => $chunk],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
    $valid = json_decode($json) !== null;
    $len = strlen($json);
    echo "$label: ".($valid ? 'OK' : 'CORRUPT')." len=$len count=".count($chunk).PHP_EOL;

    return $valid;
}

// Test seluruh data dulu
testChunk($employees, 'ALL');

// Binary search
$start = 0;
$end = $total;

while ($end - $start > 1) {
    $mid = (int) (($start + $end) / 2);

    $leftOk = testChunk(array_slice($employees, 0, $mid), "0-$mid");
    $rightOk = testChunk(array_slice($employees, $mid), "$mid-$end");

    if (! $leftOk) {
        $end = $mid;
    } elseif (! $rightOk) {
        $start = $mid;
    } else {
        echo "Both halves OK but full fails - boundary issue at $mid".PHP_EOL;
        // Cek gabungan sekitar boundary
        for ($i = max(0, $mid - 3); $i <= min($total - 1, $mid + 3); $i++) {
            $e = (array) $employees[$i];
            echo "  [$i] id={$e['id']} name={$e['full_name']}".PHP_EOL;
            echo '  HEX: '.bin2hex($e['full_name']).PHP_EOL;
        }
        break;
    }
}

echo "Problem range: $start - $end".PHP_EOL;
if ($end - $start <= 5) {
    for ($i = $start; $i < $end; $i++) {
        $e = (array) $employees[$i];
        echo "SUSPECT [$i] id={$e['id']} name={$e['full_name']}".PHP_EOL;
        echo 'HEX full_name: '.bin2hex($e['full_name']).PHP_EOL;
        echo 'HEX photo_url: '.bin2hex($e['photo_url'] ?? '').PHP_EOL;
    }
}
