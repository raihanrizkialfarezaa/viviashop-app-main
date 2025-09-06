<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

echo "=== Test Variant Pricing Logic ===\n\n";

$products = Product::whereIn('id', [3, 133])->get();

foreach ($products as $product) {
    echo "Product ID: {$product->id}\n";
    echo "Name: {$product->name}\n";
    echo "Type: {$product->type}\n";
    echo "Base Price: Rp " . number_format($product->price) . "\n";
    
    $variants = $product->activeVariants;
    echo "Variants Count: {$variants->count()}\n";
    
    if ($variants->count() > 0) {
        $minPrice = $variants->min('price');
        $maxPrice = $variants->max('price');
        
        echo "Min Variant Price: Rp " . number_format($minPrice) . "\n";
        echo "Max Variant Price: Rp " . number_format($maxPrice) . "\n";
        
        if ($minPrice == $maxPrice) {
            echo "Price Display: Rp " . number_format($minPrice) . "\n";
        } else {
            echo "Price Display: Rp " . number_format($minPrice) . " - Rp " . number_format($maxPrice) . "\n";
        }
        
        echo "Variant Options:\n";
        try {
            $variantOptions = $product->getVariantOptions();
            foreach ($variantOptions as $attr => $options) {
                echo "  - {$attr}: " . implode(', ', $options) . "\n";
            }
        } catch (Exception $e) {
            echo "  Error getting variant options: " . $e->getMessage() . "\n";
        }
        
        echo "Variants Detail:\n";
        foreach ($variants as $variant) {
            echo "  - ID: {$variant->id}, Name: {$variant->name}, Price: Rp " . number_format($variant->price) . ", Stock: {$variant->stock}\n";
        }
    }
    
    echo "\n" . str_repeat('-', 50) . "\n\n";
}

echo "=== Test completed ===\n";
