<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\AttributeOption;

try {
    $product = Product::find(117);
    $product->load(['variants.productAttributeValues.attribute']);
    
    echo "=== DEBUG SELECTED ATTRIBUTES ===\n";
    echo "Product: {$product->name}\n";
    echo "Variants count: " . $product->variants->count() . "\n\n";
    
    if ($product->variants->count() > 0) {
        $firstVariant = $product->variants->first();
        echo "First variant: {$firstVariant->name}\n";
        echo "ProductAttributeValues count: " . $firstVariant->productAttributeValues->count() . "\n\n";
        
        foreach ($firstVariant->productAttributeValues as $attrValue) {
            echo "AttributeValue ID: {$attrValue->id}\n";
            echo "Attribute: {$attrValue->attribute->name} ({$attrValue->attribute->code})\n";
            echo "Text Value: {$attrValue->text_value}\n";
            
            $attributeOption = AttributeOption::where('name', $attrValue->text_value)->with('attribute_variant')->first();
            if ($attributeOption) {
                echo "Found Option ID: {$attributeOption->id}\n";
                echo "Option Name: {$attributeOption->name}\n";
                echo "Variant ID: {$attributeOption->attribute_variant_id}\n";
                echo "Variant Name: {$attributeOption->attribute_variant->name}\n";
            } else {
                echo "Option NOT FOUND for text: {$attrValue->text_value}\n";
            }
            echo "---\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
