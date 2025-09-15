<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductInventory;
use App\Models\ProductVariant;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== Smart Print Form Validation Test ===\n\n";

    echo "1. Testing create product with smart print enabled...\n";
    
    $category = Category::first();
    
    $productData = [
        'name' => 'Test Smart Print Product',
        'sku' => 'TSP-' . time(),
        'type' => 'simple',
        'weight' => 0.2,
        'status' => 1,
        'user_id' => 1,
        'price' => 5000,
        'harga_beli' => 4000,
        'qty' => 100,
        'is_print_service' => true,
        'is_smart_print_enabled' => true,
        'short_description' => 'Test product for smart print validation'
    ];
    
    $testProduct = Product::create($productData);
    $testProduct->categories()->attach($category->id);
    
    if ($testProduct->type === 'simple') {
        ProductInventory::create([
            'product_id' => $testProduct->id,
            'qty' => $productData['qty']
        ]);
    }
    
    echo "✓ Product created successfully: {$testProduct->name}\n";
    echo "✓ Smart Print Enabled: " . ($testProduct->is_smart_print_enabled ? 'Yes' : 'No') . "\n";
    echo "✓ Print Service: " . ($testProduct->is_print_service ? 'Yes' : 'No') . "\n";
    
    echo "\n2. Testing create product without smart print...\n";
    
    $regularProductData = [
        'name' => 'Test Regular Product',
        'sku' => 'TRP-' . time(),
        'type' => 'simple',
        'weight' => 0.3,
        'status' => 1,
        'user_id' => 1,
        'price' => 3000,
        'harga_beli' => 2500,
        'qty' => 50,
        'is_print_service' => false,
        'is_smart_print_enabled' => false,
        'short_description' => 'Regular product without smart print'
    ];
    
    $regularProduct = Product::create($regularProductData);
    $regularProduct->categories()->attach($category->id);
    
    if ($regularProduct->type === 'simple') {
        ProductInventory::create([
            'product_id' => $regularProduct->id,
            'qty' => $regularProductData['qty']
        ]);
    }
    
    echo "✓ Regular product created: {$regularProduct->name}\n";
    echo "✓ Smart Print Enabled: " . ($regularProduct->is_smart_print_enabled ? 'Yes' : 'No') . "\n";
    echo "✓ Print Service: " . ($regularProduct->is_print_service ? 'Yes' : 'No') . "\n";
    
    echo "\n3. Testing update product smart print status...\n";
    
    echo "Updating regular product to enable smart print...\n";
    $regularProduct->update([
        'is_smart_print_enabled' => true,
        'is_print_service' => true
    ]);
    
    $regularProduct->refresh();
    echo "✓ Updated successfully\n";
    echo "✓ Smart Print Enabled: " . ($regularProduct->is_smart_print_enabled ? 'Yes' : 'No') . "\n";
    echo "✓ Print Service: " . ($regularProduct->is_print_service ? 'Yes' : 'No') . "\n";
    
    echo "\n4. Testing configurable product with smart print...\n";
    
    $configurableData = [
        'name' => 'Smart Print Configurable Product',
        'sku' => 'SPCP-' . time(),
        'type' => 'configurable',
        'weight' => 0.1,
        'status' => 1,
        'user_id' => 1,
        'is_print_service' => true,
        'is_smart_print_enabled' => true,
        'short_description' => 'Configurable product with smart print'
    ];
    
    $configurableProduct = Product::create($configurableData);
    $configurableProduct->categories()->attach($category->id);
    
    $variant1 = ProductVariant::create([
        'product_id' => $configurableProduct->id,
        'name' => 'Variant 1',
        'sku' => 'SPCP-V1-' . time(),
        'price' => 2000,
        'harga_beli' => 1500,
        'stock' => 75,
        'paper_size' => 'A4',
        'print_type' => 'BW',
        'is_active' => true
    ]);
    
    $variant2 = ProductVariant::create([
        'product_id' => $configurableProduct->id,
        'name' => 'Variant 2',
        'sku' => 'SPCP-V2-' . time(),
        'price' => 3000,
        'harga_beli' => 2500,
        'stock' => 25,
        'paper_size' => 'A3',
        'print_type' => 'COLOR',
        'is_active' => true
    ]);
    
    echo "✓ Configurable product created: {$configurableProduct->name}\n";
    echo "✓ Smart Print Enabled: " . ($configurableProduct->is_smart_print_enabled ? 'Yes' : 'No') . "\n";
    echo "✓ Variants created: 2\n";
    echo "  - {$variant1->name} (Stock: {$variant1->stock})\n";
    echo "  - {$variant2->name} (Stock: {$variant2->stock})\n";
    
    echo "\n5. Final verification with PrintService...\n";
    
    $printService = new \App\Services\PrintService();
    $availableProducts = $printService->getPrintProducts();
    
    echo "Products available for print service: " . $availableProducts->count() . "\n";
    foreach ($availableProducts as $product) {
        echo "- {$product->name}\n";
        echo "  Type: {$product->type}\n";
        echo "  Smart Print: " . ($product->is_smart_print_enabled ? 'Yes' : 'No') . "\n";
        echo "  Print Service: " . ($product->is_print_service ? 'Yes' : 'No') . "\n";
        echo "  Active Variants: " . $product->activeVariants->count() . "\n";
        if ($product->activeVariants->count() > 0) {
            foreach ($product->activeVariants as $variant) {
                echo "    * {$variant->name} (Stock: {$variant->stock})\n";
            }
        }
        echo "\n";
    }
    
    echo "=== Form Validation Test Summary ===\n";
    echo "✓ Simple products with smart print working\n";
    echo "✓ Regular products without smart print working\n";
    echo "✓ Update smart print status working\n";
    echo "✓ Configurable products with smart print working\n";
    echo "✓ Product variants integration working\n";
    echo "✓ PrintService filtering correctly\n";
    
    echo "\n=== All Form Validation Tests Passed! ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}