<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Attribute;
use App\Models\ProductAttributeValue;

try {
    $product = Product::find(117);
    
    if (!$product) {
        echo "Product with ID 117 not found\n";
        exit;
    }
    
    echo "Before update:\n";
    echo "Product: {$product->name}\n";
    echo "Type: {$product->type}\n";
    echo "Variants count: " . $product->variants->count() . "\n";
    
    foreach ($product->variants as $variant) {
        echo "  Variant: {$variant->name}\n";
        foreach ($variant->productAttributeValues as $attrValue) {
            echo "    - {$attrValue->attributeOption->name}\n";
        }
    }
    
    $configurable_attributes = Attribute::where('is_configurable', true)
        ->with(['attribute_variants.attribute_options'])
        ->get();
    
    echo "\nAvailable configurable attributes:\n";
    foreach ($configurable_attributes as $attr) {
        echo "Attribute: {$attr->name} ({$attr->code})\n";
        foreach ($attr->attribute_variants as $variant) {
            echo "  Variant: {$variant->name} (ID: {$variant->id})\n";
            foreach ($variant->attribute_options as $option) {
                echo "    Option: {$option->name} (ID: {$option->id})\n";
            }
        }
    }
    
    echo "\nTest completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
