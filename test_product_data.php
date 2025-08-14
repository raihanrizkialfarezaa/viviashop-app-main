<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

try {
    $product = Product::find(117);
    
    echo "Product: {$product->name}\n";
    echo "Type: {$product->type}\n";
    echo "Price: {$product->price}\n";
    echo "Weight: {$product->weight}\n";
    echo "Harga Beli: {$product->harga_beli}\n";
    echo "Length: {$product->length}\n";
    echo "Width: {$product->width}\n";
    echo "Height: {$product->height}\n";
    
    $inventory = $product->productInventory;
    if ($inventory) {
        echo "Qty: {$inventory->qty}\n";
    } else {
        echo "Qty: null (no inventory record)\n";
    }
    
    echo "Variants count: " . $product->variants->count() . "\n";
    
    foreach ($product->variants as $variant) {
        echo "  Variant: {$variant->name}\n";
        echo "    Price: {$variant->price}\n";
        echo "    Weight: {$variant->weight}\n";
        $variantInventory = $variant->productInventory;
        if ($variantInventory) {
            echo "    Qty: {$variantInventory->qty}\n";
        } else {
            echo "    Qty: null\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
