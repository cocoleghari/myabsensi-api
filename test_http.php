<?php

// test_http.php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulasi request
$request = \Illuminate\Http\Request::create('/admin/employees-dropdown', 'GET');
$request->headers->set('Accept', 'application/json');

$response = app()->handle($request);
$body = $response->getContent();

echo 'Status: '.$response->getStatusCode().PHP_EOL;
echo 'Length: '.strlen($body).PHP_EOL;

$decoded = json_decode($body, true);
echo 'JSON valid: '.($decoded !== null ? 'YES' : 'NO - '.json_last_error_msg()).PHP_EOL;

if ($decoded === null) {
    // Cari karakter bermasalah
    $pos = 0;
    for ($i = 0; $i < strlen($body); $i++) {
        $byte = ord($body[$i]);
        if ($byte > 127) {
            // Cek apakah valid UTF-8 sequence
            $char = substr($body, $i, 4);
            if (! mb_check_encoding($char, 'UTF-8')) {
                echo "BAD BYTE at pos $i: ".bin2hex($body[$i]).PHP_EOL;
                echo 'Context: '.substr($body, max(0, $i - 30), 60).PHP_EOL;
                break;
            }
        }
    }
}
