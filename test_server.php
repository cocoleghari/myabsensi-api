<?php

// test_server.php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo 'PHP Version: '.PHP_VERSION.PHP_EOL;
echo 'output_buffering: '.ini_get('output_buffering').PHP_EOL;
echo 'zlib.output_compression: '.ini_get('zlib.output_compression').PHP_EOL;
echo 'mbstring.func_overload: '.ini_get('mbstring.func_overload').PHP_EOL;

// Cek apakah ada BOM atau whitespace sebelum output
ob_start();
echo 'test';
$output = ob_get_clean();
echo 'Output length: '.strlen($output).PHP_EOL;
