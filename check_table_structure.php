<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING PRODUCTS TABLE STRUCTURE ===\n\n";

try {
    $columns = DB::select('DESCRIBE products');
    
    echo "Products table columns:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field}: {$column->Type} (Default: {$column->Default})\n";
    }
    
    echo "\n=== SAMPLE EXISTING PRODUCT ===\n";
    $sampleProduct = DB::table('products')->first();
    if ($sampleProduct) {
        echo "Sample product data:\n";
        foreach ((array)$sampleProduct as $key => $value) {
            echo "- {$key}: {$value}\n";
        }
    } else {
        echo "No products found in database.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
