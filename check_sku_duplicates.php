<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductVariant;
use App\Models\Product;

try {
    echo "=== CHECKING SKU DUPLICATES ===\n";
    
    $variants = ProductVariant::where('sku', '9787080')->get();
    echo "SKU 9787080 found in variants: " . $variants->count() . "\n";
    
    foreach($variants as $v) {
        echo "ID: {$v->id}, Product: {$v->product_id}, Name: {$v->name}\n";
    }
    
    $product = Product::where('sku', '9787080')->first();
    if ($product) {
        echo "\nProduct with SKU 9787080: ID {$product->id}, Name: {$product->name}\n";
    }
    
    echo "\n=== CHECKING ALL VARIANTS FOR PRODUCT 133 ===\n";
    $product133 = Product::find(133);
    if ($product133) {
        $allVariants = $product133->productVariants()->get();
        echo "Product 133 has " . $allVariants->count() . " variants:\n";
        foreach($allVariants as $v) {
            echo "  ID: {$v->id}, SKU: {$v->sku}, Name: {$v->name}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
