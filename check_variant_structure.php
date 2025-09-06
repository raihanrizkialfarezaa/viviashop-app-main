<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING PRODUCT_VARIANTS TABLE STRUCTURE ===\n\n";

try {
    $columns = DB::select('DESCRIBE product_variants');
    
    echo "Product_variants table columns:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field}: {$column->Type} (Default: {$column->Default})\n";
    }
    
    echo "\n=== SAMPLE EXISTING VARIANT ===\n";
    $sampleVariant = DB::table('product_variants')->first();
    if ($sampleVariant) {
        echo "Sample variant data:\n";
        foreach ((array)$sampleVariant as $key => $value) {
            echo "- {$key}: {$value}\n";
        }
    } else {
        echo "No variants found in database.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
