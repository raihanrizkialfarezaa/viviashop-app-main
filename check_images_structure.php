<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING PRODUCT_IMAGES TABLE STRUCTURE ===\n\n";

try {
    $columns = DB::select('DESCRIBE product_images');
    
    echo "Product_images table columns:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field}: {$column->Type} (Default: {$column->Default})\n";
    }
    
    echo "\n=== SAMPLE EXISTING IMAGE ===\n";
    $sampleImage = DB::table('product_images')->first();
    if ($sampleImage) {
        echo "Sample image data:\n";
        foreach ((array)$sampleImage as $key => $value) {
            echo "- {$key}: {$value}\n";
        }
    } else {
        echo "No images found in database.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
