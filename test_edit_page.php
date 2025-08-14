<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

try {
    $product = Product::find(117);
    
    echo "=== TESTING EDIT PAGE STRUCTURE ===\n";
    echo "Product: {$product->name}\n";
    echo "Type: {$product->type}\n";
    
    $hasVariants = $product->variants->count() > 0;
    echo "Has Variants: " . ($hasVariants ? 'YES' : 'NO') . "\n";
    echo "Variants Count: " . $product->variants->count() . "\n\n";
    
    echo "Parent Product Data:\n";
    echo "  SKU: {$product->sku}\n";
    echo "  Name: {$product->name}\n";
    echo "  Price: {$product->price}\n";
    echo "  Harga Beli: {$product->harga_beli}\n";
    echo "  Weight: {$product->weight}\n";
    echo "  Length: {$product->length}\n";
    echo "  Width: {$product->width}\n";
    echo "  Height: {$product->height}\n";
    
    $inventory = $product->productInventory;
    echo "  Qty: " . ($inventory ? $inventory->qty : 'NULL') . "\n";
    
    echo "\nForm Fields Status:\n";
    echo "  Fields should be: " . ($hasVariants ? 'READONLY' : 'EDITABLE') . "\n";
    echo "  Delete button should be: " . ($hasVariants ? 'VISIBLE' : 'HIDDEN') . "\n\n";
    
    if ($hasVariants) {
        echo "Variants:\n";
        foreach ($product->variants as $index => $variant) {
            echo "  Variant " . ($index + 1) . ": {$variant->name}\n";
            echo "    Price: " . ($variant->price ?: 'NULL') . "\n";
            echo "    Weight: " . ($variant->weight ?: 'NULL') . "\n";
            echo "    Attributes:\n";
            foreach ($variant->productAttributeValues as $attrValue) {
                echo "      - {$attrValue->attribute->name}: {$attrValue->text_value}\n";
            }
        }
    }
    
    echo "\n=== TEST COMPLETED ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
