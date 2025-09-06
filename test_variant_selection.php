<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

echo "=== Test Variant Selection Logic ===\n\n";

$product = Product::find(133);
if ($product) {
    echo "Product: {$product->name}\n";
    echo "Type: {$product->type}\n";
    
    $variants = $product->activeVariants;
    echo "Total Variants: {$variants->count()}\n\n";
    
    echo "Available Variants:\n";
    foreach ($variants as $variant) {
        echo "- ID: {$variant->id}, Name: {$variant->name}, Price: Rp " . number_format($variant->price) . "\n";
        echo "  Attributes: ";
        $attributes = [];
        foreach ($variant->variantAttributes as $attr) {
            $attributes[] = "{$attr->attribute_name}:{$attr->attribute_value}";
        }
        echo implode(', ', $attributes) . "\n";
    }
    
    echo "\nVariant Options (Groups):\n";
    try {
        $variantOptions = $product->getVariantOptions();
        foreach ($variantOptions as $attr => $options) {
            echo "- {$attr}: " . implode(', ', $options) . "\n";
        }
    } catch (Exception $e) {
        echo "Error getting variant options: " . $e->getMessage() . "\n";
    }
    
    echo "\nSelection Test Cases:\n";
    
    echo "1. Select only 'Putih': Should find variant with Putih attribute\n";
    $testVariants = $variants->filter(function($variant) {
        return $variant->variantAttributes->contains(function($attr) {
            return $attr->attribute_name === 'Putih';
        });
    });
    echo "   Found {$testVariants->count()} matching variants\n";
    
    echo "2. Select 'blue:panda': Should find exact variant\n";
    $testVariants = $variants->filter(function($variant) {
        return $variant->variantAttributes->contains(function($attr) {
            return $attr->attribute_name === 'blue' && $attr->attribute_value === 'panda';
        });
    });
    echo "   Found {$testVariants->count()} matching variants\n";
    
    if ($testVariants->count() > 0) {
        $exactVariant = $testVariants->first();
        echo "   Exact variant: {$exactVariant->name} - Rp " . number_format($exactVariant->price) . "\n";
    }
}

echo "\n=== Test completed ===\n";
