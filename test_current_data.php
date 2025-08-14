<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\AttributeOption;

try {
    $product = Product::find(117);
    $product->load(['variants.productAttributeValues.attribute']);
    
    echo "Product: {$product->name}\n";
    echo "Type: {$product->type}\n";
    echo "Variants count: " . $product->variants->count() . "\n\n";
    
    $selected_attributes = [];
    if ($product->type == 'configurable' && $product->variants->count() > 0) {
        $firstVariant = $product->variants->first();
        if ($firstVariant && $firstVariant->productAttributeValues->count() > 0) {
            echo "Processing variant: {$firstVariant->name}\n";
            foreach ($firstVariant->productAttributeValues as $attrValue) {
                echo "  AttributeValue ID: {$attrValue->id}\n";
                echo "  Attribute ID: {$attrValue->attribute_id}\n";
                echo "  Attribute Code: {$attrValue->attribute->code}\n";
                echo "  Text Value: {$attrValue->text_value}\n";
                
                $attributeCode = $attrValue->attribute->code;
                $textValue = $attrValue->text_value;
                
                $attributeOption = AttributeOption::where('name', $textValue)->with('attribute_variant')->first();
                if ($attributeOption) {
                    echo "  Found Option: {$attributeOption->name}\n";
                    echo "  Variant: {$attributeOption->attribute_variant->name}\n";
                    
                    $selected_attributes[$attributeCode] = [
                        'variant_id' => $attributeOption->attribute_variant_id,
                        'option_id' => $attributeOption->id,
                        'variant_name' => $attributeOption->attribute_variant->name,
                        'option_name' => $attributeOption->name
                    ];
                } else {
                    echo "  Option not found for text: {$textValue}\n";
                }
                echo "\n";
            }
        }
    }
    
    echo "Selected attributes:\n";
    print_r($selected_attributes);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
