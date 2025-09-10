<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

echo "=== DEBUGGING SIMPLE PRODUCTS ===\n";

// Check product 3
echo "\n--- PRODUCT 3 ---\n";
$product3 = Product::with(['productVariants', 'activeVariants', 'productInventory'])->find(3);
if ($product3) {
    echo "ID: {$product3->id}\n";
    echo "Name: {$product3->name}\n";
    echo "Type: {$product3->type}\n";
    echo "Parent ID: " . ($product3->parent_id ?? 'null') . "\n";
    echo "Product Variants Count: " . $product3->productVariants->count() . "\n";
    echo "Active Variants Count: " . $product3->activeVariants()->count() . "\n";
    echo "Product Inventory: " . ($product3->productInventory ? $product3->productInventory->qty : 'No inventory') . "\n";
    
    if ($product3->productVariants->count() > 0) {
        echo "--- Product Variants ---\n";
        foreach ($product3->productVariants as $variant) {
            echo "  Variant ID: {$variant->id}, Name: {$variant->name}, Active: " . ($variant->is_active ? 'Yes' : 'No') . ", Stock: {$variant->stock}\n";
        }
    }
} else {
    echo "Product 3 not found\n";
}

// Check product 4
echo "\n--- PRODUCT 4 ---\n";
$product4 = Product::with(['productVariants', 'activeVariants', 'productInventory'])->find(4);
if ($product4) {
    echo "ID: {$product4->id}\n";
    echo "Name: {$product4->name}\n";
    echo "Type: {$product4->type}\n";
    echo "Parent ID: " . ($product4->parent_id ?? 'null') . "\n";
    echo "Product Variants Count: " . $product4->productVariants->count() . "\n";
    echo "Active Variants Count: " . $product4->activeVariants()->count() . "\n";
    echo "Product Inventory: " . ($product4->productInventory ? $product4->productInventory->qty : 'No inventory') . "\n";
    
    if ($product4->productVariants->count() > 0) {
        echo "--- Product Variants ---\n";
        foreach ($product4->productVariants as $variant) {
            echo "  Variant ID: {$variant->id}, Name: {$variant->name}, Active: " . ($variant->is_active ? 'Yes' : 'No') . ", Stock: {$variant->stock}\n";
        }
    }
} else {
    echo "Product 4 not found\n";
}

// Check all simple products that have variants (data inconsistency)
echo "\n--- SIMPLE PRODUCTS WITH VARIANTS (DATA INCONSISTENCY) ---\n";
$simpleWithVariants = Product::where('type', 'simple')
    ->whereHas('productVariants')
    ->with(['productVariants'])
    ->get();

foreach ($simpleWithVariants as $product) {
    echo "Product ID: {$product->id}, Name: {$product->name}, Variants: " . $product->productVariants->count() . "\n";
}

echo "\nDone.\n";
