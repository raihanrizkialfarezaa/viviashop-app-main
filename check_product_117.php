<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

try {
    $product = Product::find(117);
    
    if (!$product) {
        echo "Product with ID 117 not found\n";
        exit;
    }
    
    echo "Product ID: {$product->id}\n";
    echo "Product Name: {$product->name}\n";
    echo "Product Type: {$product->type}\n";
    echo "Product Price: {$product->price}\n";
    echo "Product Weight: {$product->weight}\n";
    echo "Product SKU: {$product->sku}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
