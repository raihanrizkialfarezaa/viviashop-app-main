<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\AttributeOption;

echo "Testing Dropdown Persistence\n";
echo "============================\n\n";

// Cari product configurable yang memiliki variants
$configurableProduct = Product::where('type', 'configurable')
    ->whereHas('variants')
    ->first();

if (!$configurableProduct) {
    echo "❌ No configurable product with variants found\n";
    exit;
}

echo "✅ Found configurable product: {$configurableProduct->name} (ID: {$configurableProduct->id})\n";

// Load relationships seperti di edit method
$configurableProduct->load(['variants.variantAttributeValues.attribute']);

echo "📊 Variants count: " . $configurableProduct->variants->count() . "\n\n";

$selected_attributes = [];
if ($configurableProduct->variants->count() > 0) {
    $firstVariant = $configurableProduct->variants->first();
    echo "🔍 Checking first variant: {$firstVariant->name} (ID: {$firstVariant->id})\n";
    echo "📊 Variant attribute values count: " . $firstVariant->variantAttributeValues->count() . "\n\n";
    
    if ($firstVariant->variantAttributeValues->count() > 0) {
        foreach ($firstVariant->variantAttributeValues as $attrValue) {
            echo "🔸 Processing attribute value:\n";
            echo "   - Attribute Code: {$attrValue->attribute->code}\n";
            echo "   - Text Value: {$attrValue->text_value}\n";
            
            $attributeCode = $attrValue->attribute->code;
            $textValue = $attrValue->text_value;
            
            $attributeOption = AttributeOption::where('name', $textValue)->with('attribute_variant')->first();
            if ($attributeOption) {
                echo "   - Found matching option: {$attributeOption->name}\n";
                echo "   - Variant ID: {$attributeOption->attribute_variant_id}\n";
                echo "   - Variant Name: {$attributeOption->attribute_variant->name}\n";
                
                $selected_attributes[$attributeCode] = [
                    'variant_id' => $attributeOption->attribute_variant_id,
                    'option_id' => $attributeOption->id,
                    'variant_name' => $attributeOption->attribute_variant->name,
                    'option_name' => $attributeOption->name
                ];
            } else {
                echo "   - ❌ No matching AttributeOption found for: {$textValue}\n";
            }
            echo "\n";
        }
    }
}

echo "📋 Selected Attributes Array:\n";
if (empty($selected_attributes)) {
    echo "❌ Empty - No attributes found\n";
} else {
    foreach ($selected_attributes as $code => $data) {
        echo "✅ {$code}: {$data['variant_name']} → {$data['option_name']}\n";
    }
}

echo "\n🎯 Test Result: ";
if (!empty($selected_attributes)) {
    echo "✅ SUCCESS - Dropdown persistence should work!\n";
} else {
    echo "❌ FAILED - No selected attributes found\n";
}
