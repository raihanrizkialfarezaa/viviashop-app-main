<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\AttributeOption;
use App\Models\ProductAttributeValue;

try {
    $product = Product::find(117);
    
    $existingVariant = $product->variants->first();
    if ($existingVariant) {
        echo "Creating ProductAttributeValue for existing variant...\n";
        
        $optionId = 1; 
        $option = AttributeOption::with(['attribute_variant.attribute'])->find($optionId);
        
        if ($option) {
            $attributeValueParams = [
                'parent_product_id' => $product->id,
                'product_id' => $existingVariant->id,
                'attribute_id' => $option->attribute_variant->attribute->id,
                'attribute_variant_id' => $option->attribute_variant->id,
                'attribute_option_id' => $option->id,
                'text_value' => $option->name,
            ];
            
            $existing = ProductAttributeValue::where('product_id', $existingVariant->id)
                                            ->where('attribute_option_id', $optionId)
                                            ->first();
            
            if (!$existing) {
                ProductAttributeValue::create($attributeValueParams);
                echo "ProductAttributeValue created successfully!\n";
            } else {
                echo "ProductAttributeValue already exists!\n";
            }
            
            echo "Option: {$option->name}\n";
            echo "Variant: {$option->attribute_variant->name}\n";
            echo "Attribute: {$option->attribute_variant->attribute->name}\n";
        }
    }
    
    $product->load(['variants.productAttributeValues.attributeOption.attribute_variant.attribute']);
    
    $selected_attributes = [];
    if ($product->type == 'configurable' && $product->variants->count() > 0) {
        $firstVariant = $product->variants->first();
        if ($firstVariant && $firstVariant->productAttributeValues->count() > 0) {
            foreach ($firstVariant->productAttributeValues as $attrValue) {
                $attributeCode = $attrValue->attributeOption->attribute_variant->attribute->code;
                $variantId = $attrValue->attribute_variant_id;
                $optionId = $attrValue->attribute_option_id;
                
                $selected_attributes[$attributeCode] = [
                    'variant_id' => $variantId,
                    'option_id' => $optionId,
                    'variant_name' => $attrValue->attributeOption->attribute_variant->name,
                    'option_name' => $attrValue->attributeOption->name
                ];
            }
        }
    }
    
    echo "\nSelected attributes after creating test data:\n";
    print_r($selected_attributes);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
