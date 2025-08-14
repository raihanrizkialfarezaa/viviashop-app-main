<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

echo "=== CHECKING VARIANT DATA ===\n\n";

$product = Product::with(['productInventory', 'variants.productInventory'])->find(117);

echo "Product: {$product->name}\n";
echo "Type: {$product->type}\n";
echo "Variants count: " . $product->variants->count() . "\n\n";

foreach ($product->variants as $variant) {
    echo "Variant {$variant->id}: {$variant->name}\n";
    
    $variant->product_attribute_values = \App\Models\ProductAttributeValue::where('product_id', $variant->id)
        ->with(['attribute', 'attribute_variant', 'attribute_option'])
        ->get();
    
    echo "  Attribute values count: " . $variant->product_attribute_values->count() . "\n";
    
    foreach ($variant->product_attribute_values as $pav) {
        echo "    - Attribute: " . ($pav->attribute ? $pav->attribute->code : 'NULL') . "\n";
        echo "      Variant: " . ($pav->attribute_variant ? $pav->attribute_variant->id : 'NULL') . "\n";
        echo "      Option: " . ($pav->attribute_option ? $pav->attribute_option->id : 'NULL') . "\n";
    }
    echo "\n";
}

echo "=== JAVASCRIPT DATA TEST ===\n";
$variants = $product->variants()->with(['productInventory'])->get();

foreach ($variants as $variant) {
    $variant->product_attribute_values = \App\Models\ProductAttributeValue::where('product_id', $variant->id)
        ->with(['attribute', 'attribute_variant', 'attribute_option'])
        ->get();
}

echo "Variants data for JavaScript:\n";
echo json_encode($variants, JSON_PRETTY_PRINT) . "\n";

echo "\n=== TEST COMPLETED ===\n";
