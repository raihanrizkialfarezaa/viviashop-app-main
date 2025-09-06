<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

try {
    echo "Testing product edit page data...\n";
    
    // Get first product
    $product = Product::first();
    if (!$product) {
        echo "No products found\n";
        exit;
    }
    
    echo "Product: {$product->id} - {$product->name}\n";
    echo "Product Type: {$product->type}\n";
    echo "Base Price: " . number_format($product->price, 0, ',', '.') . "\n";
    
    // Check variants
    $variants = $product->productVariants()->with('variantAttributes')->get();
    echo "Variants count: " . $variants->count() . "\n";
    
    foreach ($variants as $variant) {
        echo "  - Variant: {$variant->name} (SKU: {$variant->sku})\n";
        echo "    Price: " . number_format($variant->price, 0, ',', '.') . "\n";
        echo "    Stock: {$variant->stock}\n";
        
        foreach ($variant->variantAttributes as $attr) {
            echo "    Attribute: {$attr->attribute_name} = {$attr->attribute_value}\n";
        }
        echo "\n";
    }
    
    echo "Test completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
