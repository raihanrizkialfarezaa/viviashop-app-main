<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\ProductAttributeValue;

try {
    $product = Product::find(117);
    $product->load(['variants.productAttributeValues.attributeOption.attributeVariant.attribute']);
    
    echo "Product: {$product->name}\n";
    echo "Type: {$product->type}\n";
    echo "Variants count: " . $product->variants->count() . "\n\n";
    
    if ($product->type === 'configurable') {
        $testRequest = [
            'HVS' => ['1'] 
        ];
        
        $configurableAttributes = Attribute::where('is_configurable', true)->with(['attribute_variants.attribute_options'])->get();
        $variantAttributes = [];
        
        foreach ($configurableAttributes as $attribute) {
            if (isset($testRequest[$attribute->code])) {
                $variantAttributes[$attribute->code] = $testRequest[$attribute->code];
            }
        }
        
        echo "Test creating variant with HVS option ID 1:\n";
        
        if (!empty($variantAttributes)) {
            $combinations = [[]];
            foreach ($variantAttributes as $property => $property_values) {
                $tmp = [];
                if ($property_values != null) {
                    foreach ($combinations as $result_item) {
                        foreach ($property_values as $property_value) {
                            $tmp[] = array_merge($result_item, array($property => $property_value));
                        }
                    }
                } else {
                    foreach ($combinations as $result_item) {
                        $tmp[] = array_merge($result_item, array($property => 'null'));
                    }
                }
                $combinations = $tmp;
            }
            
            echo "Generated combinations:\n";
            foreach ($combinations as $combination) {
                print_r($combination);
                
                foreach ($combination as $attributeCode => $optionId) {
                    $option = AttributeOption::with(['attribute_variant.attribute'])->find($optionId);
                    if ($option) {
                        echo "Option: {$option->name}\n";
                        echo "Variant: {$option->attribute_variant->name}\n";
                        echo "Attribute: {$option->attribute_variant->attribute->name}\n";
                    }
                }
            }
        }
    }
    
    $selected_attributes = [];
    if ($product->type == 'configurable' && $product->variants->count() > 0) {
        $firstVariant = $product->variants->first();
        if ($firstVariant && $firstVariant->productAttributeValues->count() > 0) {
            foreach ($firstVariant->productAttributeValues as $attrValue) {
                $attributeCode = $attrValue->attributeOption->attributeVariant->attribute->code;
                $variantId = $attrValue->attribute_variant_id;
                $optionId = $attrValue->attribute_option_id;
                
                $selected_attributes[$attributeCode] = [
                    'variant_id' => $variantId,
                    'option_id' => $optionId,
                    'variant_name' => $attrValue->attributeOption->attributeVariant->name,
                    'option_name' => $attrValue->attributeOption->name
                ];
            }
        }
    }
    
    echo "\nSelected attributes:\n";
    print_r($selected_attributes);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
