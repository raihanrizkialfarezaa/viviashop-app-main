<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductAttributeValue;
use App\Models\Product;

try {
    $product = Product::find(117);
    
    echo "=== CHECK PRODUCT ATTRIBUTE VALUES ===\n";
    echo "Product ID: {$product->id}\n";
    echo "Product Name: {$product->name}\n\n";
    
    $allValues = ProductAttributeValue::where('parent_product_id', $product->id)->get();
    echo "ProductAttributeValues for parent_product_id {$product->id}: " . $allValues->count() . "\n";
    
    foreach ($allValues as $value) {
        echo "  ID: {$value->id}\n";
        echo "  Product ID: {$value->product_id}\n";
        echo "  Parent Product ID: {$value->parent_product_id}\n";
        echo "  Attribute ID: {$value->attribute_id}\n";
        echo "  Text Value: {$value->text_value}\n";
        echo "  ---\n";
    }
    
    echo "\nVariants:\n";
    foreach ($product->variants as $variant) {
        echo "Variant ID: {$variant->id}, Name: {$variant->name}\n";
        
        $variantValues = ProductAttributeValue::where('product_id', $variant->id)->get();
        echo "  AttributeValues: " . $variantValues->count() . "\n";
        
        foreach ($variantValues as $value) {
            echo "    Text: {$value->text_value}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
