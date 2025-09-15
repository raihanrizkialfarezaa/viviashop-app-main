<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

echo "Testing fixed relationship...\n\n";

echo "=== Products Without ProductVariants (FIXED) ===\n";
$productsWithoutVariants = Product::where('is_print_service', true)
    ->where('status', 1)
    ->whereDoesntHave('productVariants')  // Use correct relationship
    ->get();

echo "Found {$productsWithoutVariants->count()} print service products without variants:\n";
foreach($productsWithoutVariants as $product) {
    $variantCount = $product->productVariants()->count();
    echo "- {$product->name} (ProductVariants: {$variantCount})\n";
}

echo "\n=== Verification: Products WITH ProductVariants ===\n";
$productsWithVariants = Product::where('is_print_service', true)
    ->where('status', 1)
    ->whereHas('productVariants')
    ->get();

echo "Found {$productsWithVariants->count()} print service products WITH variants:\n";
foreach($productsWithVariants as $product) {
    $variantCount = $product->productVariants()->count();
    echo "- {$product->name} (ProductVariants: {$variantCount})\n";
}