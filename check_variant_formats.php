<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductVariant;

echo "Melihat paper_size yang ada di database:\n";
$paperSizes = ProductVariant::distinct()
    ->whereNotNull('paper_size')
    ->orderBy('paper_size')
    ->pluck('paper_size');

foreach ($paperSizes as $size) {
    echo "- '" . $size . "' (length: " . strlen($size) . ")\n";
}

echo "\nMelihat print_type yang ada di database:\n";
$printTypes = ProductVariant::distinct()
    ->whereNotNull('print_type')
    ->orderBy('print_type')
    ->pluck('print_type');

foreach ($printTypes as $type) {
    echo "- '" . $type . "' (length: " . strlen($type) . ")\n";
}