<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantAttribute;

echo "=== CLEANING DATA INCONSISTENCY ===\n";

// Find simple products that have variants
$simpleWithVariants = Product::where('type', 'simple')
    ->whereHas('productVariants')
    ->with(['productVariants'])
    ->get();

echo "Found " . $simpleWithVariants->count() . " simple products with variants:\n";

foreach ($simpleWithVariants as $product) {
    echo "\nProduct ID: {$product->id} - {$product->name}\n";
    echo "Variants count: " . $product->productVariants->count() . "\n";
    
    // Option 1: Remove variants and keep as simple product
    echo "Removing variants for simple product...\n";
    
    foreach ($product->productVariants as $variant) {
        echo "  Removing variant: {$variant->name}\n";
        
        // Delete variant attributes first
        VariantAttribute::where('variant_id', $variant->id)->delete();
        
        // Delete the variant
        $variant->delete();
    }
    
    echo "✓ Cleaned variants for product {$product->id}\n";
}

// Verify cleanup
echo "\n--- Verification ---\n";
$remainingInconsistent = Product::where('type', 'simple')
    ->whereHas('productVariants')
    ->count();

echo "Remaining simple products with variants: {$remainingInconsistent}\n";

if ($remainingInconsistent == 0) {
    echo "✓ All data inconsistencies cleaned!\n";
} else {
    echo "✗ Some inconsistencies remain\n";
}

echo "\nCleanup completed.\n";
