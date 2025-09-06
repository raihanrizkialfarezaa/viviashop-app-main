<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

try {
    echo "Testing Product ID 4...\n";
    
    $product = Product::with(['activeVariants'])->find(4);
    
    if (!$product) {
        echo "Product not found\n";
        exit;
    }
    
    echo "Product Name: {$product->name}\n";
    echo "Product Type: {$product->type}\n";
    
    $variants = $product->activeVariants;
    echo "Variants count: " . $variants->count() . "\n";
    
    if ($variants->count() > 0) {
        foreach ($variants as $variant) {
            echo "Variant ID: {$variant->id} - {$variant->name}\n";
        }
        
        // Test getVariantOptions
        try {
            $variantOptions = $product->getVariantOptions();
            echo "VariantOptions: " . json_encode($variantOptions) . "\n";
        } catch (Exception $e) {
            echo "getVariantOptions error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Simple product - no variant options needed\n";
    }
    
    echo "\nTest completed successfully\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
