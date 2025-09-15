<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

echo "=== TEST CREATE SMART PRINT PRODUCT ===\n\n";

// Simulate form data with checkboxes checked
$formData = [
    'name' => 'Test Smart Print Form',
    'sku' => 'TSP-FORM-001',
    'type' => 'simple',
    'price' => 3000,
    'harga_beli' => 1500,
    'weight' => 0.1,
    'status' => 1,
    'user_id' => 1,
    'short_description' => 'Test form submission',
    'description' => 'Test description',
    // Checkbox values - ini yang harus di-set true di form
    'is_print_service' => true,
    'is_smart_print_enabled' => true,
];

echo "Membuat produk dengan data form:\n";
foreach ($formData as $key => $value) {
    if (is_bool($value)) {
        echo "- $key: " . ($value ? 'true' : 'false') . "\n";
    } else {
        echo "- $key: $value\n";
    }
}

// Create product
$product = new Product();
$product->fill($formData);
$product->save();

echo "\n✓ Produk berhasil dibuat: " . $product->name . " (ID: " . $product->id . ")\n";

// Check if values are saved correctly
$savedProduct = Product::find($product->id);
echo "\nValues tersimpan di database:\n";
echo "- is_print_service: " . ($savedProduct->is_print_service ? 'true' : 'false') . "\n";
echo "- is_smart_print_enabled: " . ($savedProduct->is_smart_print_enabled ? 'true' : 'false') . "\n";

// Simulate auto-create variants (like in controller)
if ($savedProduct->is_print_service && $savedProduct->is_smart_print_enabled) {
    echo "\n✓ Conditions met for auto-create variants. Creating...\n";
    
    $defaultVariants = [
        [
            'name' => $savedProduct->name . ' - Black & White',
            'sku' => $savedProduct->sku . '-BW',
            'paper_size' => 'A4',
            'print_type' => 'bw',
            'stock' => 100,
            'price' => $savedProduct->price,
            'harga_beli' => $savedProduct->harga_beli,
        ],
        [
            'name' => $savedProduct->name . ' - Color',
            'sku' => $savedProduct->sku . '-CLR',
            'paper_size' => 'A4',
            'print_type' => 'color',
            'stock' => 50,
            'price' => $savedProduct->price * 1.5,
            'harga_beli' => $savedProduct->harga_beli,
        ]
    ];
    
    foreach ($defaultVariants as $variantData) {
        $variant = new ProductVariant();
        $variant->product_id = $savedProduct->id;
        $variant->sku = $variantData['sku'];
        $variant->name = $variantData['name'];
        $variant->price = $variantData['price'];
        $variant->harga_beli = $variantData['harga_beli'];
        $variant->stock = $variantData['stock'];
        $variant->weight = $savedProduct->weight;
        $variant->print_type = $variantData['print_type'];
        $variant->paper_size = $variantData['paper_size'];
        $variant->is_active = true;
        $variant->min_stock_threshold = $variantData['stock'] * 0.1;
        $variant->save();
        
        echo "✓ Variant created: " . $variant->name . "\n";
    }
} else {
    echo "\n❌ Conditions NOT met for auto-create variants\n";
    echo "- is_print_service: " . ($savedProduct->is_print_service ? 'true' : 'false') . "\n";
    echo "- is_smart_print_enabled: " . ($savedProduct->is_smart_print_enabled ? 'true' : 'false') . "\n";
}

// Test stock management filter
echo "\nTesting stock management filter:\n";
$stockService = new \App\Services\StockManagementService();
$allVariants = $stockService->getVariantsByStock('asc');
$testProductVariants = $allVariants->filter(function($variant) use ($savedProduct) {
    return $variant->product_id == $savedProduct->id;
});

echo "✓ Product variants in stock management: " . $testProductVariants->count() . "\n";

// Cleanup
echo "\nCleaning up test data...\n";
ProductVariant::where('product_id', $savedProduct->id)->delete();
$savedProduct->delete();
echo "✓ Test data cleaned up\n";

echo "\n=== TEST SELESAI ===\n";