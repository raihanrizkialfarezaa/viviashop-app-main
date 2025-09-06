<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

try {
    echo "Checking available products...\n";
    
    $products = Product::select('id', 'name', 'type')->take(10)->get();
    
    foreach ($products as $product) {
        echo "Product ID: {$product->id} - {$product->name} - Type: {$product->type}\n";
    }
    
    echo "\nTest completed successfully\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
