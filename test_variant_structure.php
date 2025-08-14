<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

try {
    $product = Product::find(117);
    
    echo "=== PRODUCT STRUCTURE TEST ===\n";
    echo "Product: {$product->name}\n";
    echo "Type: {$product->type}\n";
    echo "Parent Product Data:\n";
    echo "  Price: {$product->price}\n";
    echo "  Weight: {$product->weight}\n";
    echo "  SKU: {$product->sku}\n\n";
    
    echo "Variants:\n";
    foreach ($product->variants as $index => $variant) {
        echo "  Variant " . ($index + 1) . ": {$variant->name}\n";
        echo "    ID: {$variant->id}\n";
        echo "    SKU: {$variant->sku}\n";
        echo "    Price: " . ($variant->price ?: 'NULL') . "\n";
        echo "    Harga Beli: " . ($variant->harga_beli ?: 'NULL') . "\n";
        echo "    Weight: " . ($variant->weight ?: 'NULL') . "\n";
        echo "    Length: " . ($variant->length ?: 'NULL') . "\n";
        echo "    Width: " . ($variant->width ?: 'NULL') . "\n";
        echo "    Height: " . ($variant->height ?: 'NULL') . "\n";
        
        $inventory = $variant->productInventory;
        echo "    Qty: " . ($inventory ? $inventory->qty : 'NULL') . "\n";
        
        echo "    Attributes:\n";
        foreach ($variant->productAttributeValues as $attrValue) {
            echo "      - {$attrValue->attribute->name}: {$attrValue->text_value}\n";
        }
        echo "\n";
    }
    
    echo "=== TEST COMPLETED ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
