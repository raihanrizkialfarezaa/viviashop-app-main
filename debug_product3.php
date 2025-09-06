<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

try {
    echo "Testing Product ID 3...\n";
    
    $product = Product::find(3);
    if (!$product) {
        echo "Product not found\n";
        exit;
    }
    
    echo "Product Name: " . $product->name . "\n";
    echo "Product Type: " . $product->type . "\n";
    
    // Check variants
    $variants = $product->activeVariants;
    echo "Variants count: " . $variants->count() . "\n";
    
    if ($variants->count() > 0) {
        foreach ($variants as $variant) {
            echo "Variant ID: " . $variant->id . " - " . $variant->name . "\n";
        }
    }
    
    // Check if this is causing the issue
    if ($product->type === 'configurable') {
        $variantOptions = $product->getVariantOptions();
        echo "Variant options available\n";
    } else {
        echo "Simple product - no variant options needed\n";
    }
    
    echo "Test completed successfully\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
