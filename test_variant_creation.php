<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

try {
    echo "Testing variant creation...\n";
    
    // Get first product
    $product = Product::first();
    if (!$product) {
        echo "No products found\n";
        exit;
    }
    
    echo "Product: {$product->id} - {$product->name}\n";
    
    // Test creating variant with raw data
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Test Variant - ' . date('Y-m-d H:i:s'),
        'sku' => 'TEST-VAR-' . time(),
        'price' => 100000,
        'stock' => 10,
        'weight' => 100,
        'is_active' => true,
    ]);
    
    echo "Variant created: {$variant->id} - {$variant->name}\n";
    echo "Variant SKU: {$variant->sku}\n";
    
    echo "Test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
