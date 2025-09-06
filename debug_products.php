<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$productIds = [4, 5, 121];

foreach ($productIds as $id) {
    echo "=== Product ID: $id ===\n";
    
    try {
        $product = Product::find($id);
        
        if (!$product) {
            echo "Product not found\n";
            continue;
        }
        
        echo "Name: {$product->name}\n";
        echo "Type: {$product->type}\n";
        echo "Price: Rp " . number_format($product->price) . "\n";
        echo "Parent ID: " . ($product->parent_id ?: 'null') . "\n";
        
        if ($product->parent_id) {
            echo "This is a variant product, should redirect to parent\n";
            $parent = Product::find($product->parent_id);
            if ($parent) {
                echo "Parent: {$parent->name} (ID: {$parent->id})\n";
            }
        }
        
        $variants = $product->activeVariants;
        echo "Variants count: {$variants->count()}\n";
        
        $inventory = $product->productInventory;
        echo "Has inventory: " . ($inventory ? 'Yes' : 'No') . "\n";
        if ($inventory) {
            echo "Stock: {$inventory->qty}\n";
        }
        
        echo "Product Images: {$product->productImages->count()}\n";
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    echo "\n" . str_repeat('-', 40) . "\n\n";
}
