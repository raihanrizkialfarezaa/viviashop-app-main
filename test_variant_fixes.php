<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

try {
    echo "=== TESTING VARIANT SYSTEM FIXES ===\n\n";
    
    $product = Product::find(133);
    if (!$product) {
        echo "Product 133 not found\n";
        exit;
    }
    
    echo "Product: {$product->id} - {$product->name}\n";
    echo "Product Type: {$product->type}\n";
    echo "Product Weight: {$product->weight}\n";
    echo "Product Length: {$product->length}\n";
    echo "Product Width: {$product->width}\n";
    echo "Product Height: {$product->height}\n\n";
    
    $variants = $product->productVariants()->with('variantAttributes')->get();
    echo "Total Variants: " . $variants->count() . "\n\n";
    
    foreach ($variants as $index => $variant) {
        echo "Variant " . ($index + 1) . ":\n";
        echo "  ID: {$variant->id}\n";
        echo "  Name: {$variant->name}\n";
        echo "  SKU: {$variant->sku}\n";
        echo "  Price: " . number_format($variant->price, 0, ',', '.') . "\n";
        echo "  Stock: {$variant->stock}\n";
        echo "  Weight: {$variant->weight}\n";
        echo "  Attributes:\n";
        
        foreach ($variant->variantAttributes as $attr) {
            echo "    - {$attr->attribute_name}: {$attr->attribute_value}\n";
        }
        echo "\n";
    }
    
    echo "=== TESTING CREATE NEW VARIANT ===\n";
    
    $testVariant = ProductVariant::create([
        'product_id' => $product->id,
        'name' => 'Test Fix Variant - ' . date('H:i:s'),
        'sku' => 'FIX-TEST-' . time(),
        'price' => 250000,
        'stock' => 25,
        'weight' => 400,
        'is_active' => true,
    ]);
    
    echo "New Variant Created:\n";
    echo "  ID: {$testVariant->id}\n";
    echo "  Name: {$testVariant->name}\n";
    echo "  SKU: {$testVariant->sku}\n";
    echo "  Price: " . number_format($testVariant->price, 0, ',', '.') . "\n\n";
    
    $product->refresh();
    echo "Product Type After Variant Creation: {$product->type}\n";
    
    echo "\n=== ALL TESTS COMPLETED ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
