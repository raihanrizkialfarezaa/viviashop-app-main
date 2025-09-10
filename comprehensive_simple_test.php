<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

echo "=== COMPREHENSIVE SIMPLE PRODUCT TEST ===\n";

// Test adding simple products to cart
echo "\n--- Testing Cart Add for Simple Products ---\n";

// Test product 3 (simple with inconsistent variants)
$product3 = Product::find(3);
echo "Product 3: {$product3->name} (Type: {$product3->type})\n";
echo "Has variants: " . ($product3->productVariants->count() > 0 ? 'Yes' : 'No') . "\n";
echo "Active variants: " . $product3->activeVariants()->count() . "\n";
echo "Stock: " . ($product3->productInventory ? $product3->productInventory->qty : 'No inventory') . "\n";

// Test product 4 (proper simple product)
$product4 = Product::find(4);
echo "\nProduct 4: {$product4->name} (Type: {$product4->type})\n";
echo "Has variants: " . ($product4->productVariants->count() > 0 ? 'Yes' : 'No') . "\n";
echo "Active variants: " . $product4->activeVariants()->count() . "\n";
echo "Stock: " . ($product4->productInventory ? $product4->productInventory->qty : 'No inventory') . "\n";

// Test other simple products
echo "\n--- All Simple Products ---\n";
$simpleProducts = Product::where('type', 'simple')->with(['productVariants', 'productInventory'])->get();
foreach ($simpleProducts as $product) {
    $hasVariants = $product->productVariants->count() > 0;
    $stock = $product->productInventory ? $product->productInventory->qty : 0;
    echo "ID: {$product->id}, Name: {$product->name}, Variants: " . ($hasVariants ? 'Yes' : 'No') . ", Stock: {$stock}\n";
    
    if ($hasVariants) {
        echo "  ⚠️  Data inconsistency: Simple product has variants\n";
    }
}

// Test configurable products
echo "\n--- Configurable Products ---\n";
$configurableProducts = Product::where('type', 'configurable')->with(['productVariants'])->get();
foreach ($configurableProducts as $product) {
    echo "ID: {$product->id}, Name: {$product->name}, Variants: " . $product->productVariants->count() . "\n";
}

echo "\nTest completed.\n";
