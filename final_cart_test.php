<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

echo "=== FINAL CART BUTTON TEST ===\n\n";

$products = [3, 133];

foreach ($products as $productId) {
    echo "Testing Product ID: $productId\n";
    
    $product = Product::find($productId);
    if (!$product) {
        echo "Product not found\n\n";
        continue;
    }
    
    echo "Name: {$product->name}\n";
    echo "Type: {$product->type}\n";
    
    $variants = $product->activeVariants;
    echo "Variants: {$variants->count()}\n";
    
    $hasVariants = $product->type === 'configurable' || 
                   ($product->type === 'simple' && $variants->count() > 0);
    
    echo "Has variants (requires selection): " . ($hasVariants ? 'YES' : 'NO') . "\n";
    
    if ($hasVariants && $variants->count() > 0) {
        echo "Variant Options:\n";
        try {
            $variantOptions = $product->getVariantOptions();
            foreach ($variantOptions as $attr => $options) {
                echo "  - {$attr}: " . implode(', ', $options) . "\n";
            }
        } catch (Exception $e) {
            echo "  Error: " . $e->getMessage() . "\n";
        }
        
        echo "Test Cases:\n";
        
        // Test case 1: Select first available option
        $firstVariant = $variants->first();
        if ($firstVariant && $firstVariant->variantAttributes->count() > 0) {
            $firstAttr = $firstVariant->variantAttributes->first();
            echo "  Case 1: Select {$firstAttr->attribute_name}:{$firstAttr->attribute_value}\n";
            echo "    Should enable cart button: YES\n";
            echo "    Expected variant: {$firstVariant->name} - Rp " . number_format($firstVariant->price) . "\n";
        }
        
        // Test case 2: Invalid combination
        echo "  Case 2: Select invalid combination\n";
        echo "    Should enable cart button: NO\n";
        echo "    Expected message: 'Kombinasi varian tidak tersedia'\n";
        
    } else {
        echo "Cart button: Always enabled (simple product)\n";
    }
    
    echo "\n" . str_repeat('-', 50) . "\n\n";
}

echo "=== EXPECTED BEHAVIOR ===\n";
echo "1. Simple products without variants: Cart button always enabled\n";
echo "2. Products with variants: Cart button enabled only when valid variant selected\n";
echo "3. Single selection per attribute sufficient (not all attributes required)\n";
echo "4. Price updates to selected variant price\n";
echo "5. Variant info shows when valid selection made\n\n";

echo "=== JavaScript Logic Summary ===\n";
echo "- findExactVariant(): Returns variant matching selected attributes\n";
echo "- updateCartButton(): Enables if exactVariant found\n";
echo "- updatePriceRange(): Shows exact price when variant selected\n";
echo "- No requirement for all attributes to be selected\n\n";

echo "=== TEST COMPLETE ===\n";
