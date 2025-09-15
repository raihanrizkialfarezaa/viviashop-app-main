<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ProductVariantService;
use App\Models\Product;

echo "=== DIRECT TEST: PRODUCTVARIANTSERVICE ===\n\n";

// Test data dengan checkbox true
$testData = [
    'name' => 'Direct Test Product',
    'sku' => 'DIRECT-TEST-' . time(),
    'type' => 'simple',
    'status' => 1,
    'price' => 5000,
    'weight' => 0.1,
    'brand_id' => 1,
    'is_print_service' => true,
    'is_smart_print_enabled' => true,
];

echo "Test data:\n";
foreach ($testData as $key => $value) {
    echo "- {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
}
echo "\n";

// Direct test ProductVariantService
echo "=== TESTING PRODUCTVARIANTSERVICE::CREATEBASEPRODUCT ===\n";

try {
    $service = new ProductVariantService();
    $product = $service->createBaseProduct($testData);
    
    echo "✅ Product created successfully\n";
    echo "- ID: {$product->id}\n";
    echo "- Name: {$product->name}\n";
    echo "- SKU: {$product->sku}\n";
    
    echo "\nChecking checkbox values after creation:\n";
    $product->refresh(); // Refresh from database
    
    echo "- is_print_service: " . ($product->is_print_service ? 'true' : 'false') . " (raw: {$product->is_print_service})\n";
    echo "- is_smart_print_enabled: " . ($product->is_smart_print_enabled ? 'true' : 'false') . " (raw: {$product->is_smart_print_enabled})\n";
    
    // Check raw database values
    $rawProduct = DB::table('products')->where('id', $product->id)->first();
    echo "\nRaw database values:\n";
    echo "- is_print_service (DB): {$rawProduct->is_print_service}\n";
    echo "- is_smart_print_enabled (DB): {$rawProduct->is_smart_print_enabled}\n";
    
    if ($rawProduct->is_print_service == 1 && $rawProduct->is_smart_print_enabled == 1) {
        echo "\n✅ SUCCESS: ProductVariantService correctly saves checkbox values!\n";
    } else {
        echo "\n❌ FAILED: ProductVariantService not saving checkbox values correctly\n";
        
        // Debug the exact data being passed to create()
        echo "\nDEBUG: Let's trace what happens...\n";
        
        // Test direct Product::create with same data
        echo "Testing direct Product::create with same data...\n";
        
        $directTestData = [
            'name' => 'Direct Create Test',
            'sku' => 'DIRECT-CREATE-' . time(),
            'type' => 'simple',
            'status' => 1,
            'price' => 5000,
            'weight' => 0.1,
            'user_id' => 1,
            'is_print_service' => true,
            'is_smart_print_enabled' => true,
        ];
        
        $directProduct = Product::create($directTestData);
        $directProduct->refresh();
        
        echo "Direct Product::create result:\n";
        echo "- is_print_service: " . ($directProduct->is_print_service ? 'true' : 'false') . "\n";
        echo "- is_smart_print_enabled: " . ($directProduct->is_smart_print_enabled ? 'true' : 'false') . "\n";
        
        // Cleanup
        $directProduct->delete();
    }
    
    // Cleanup
    $product->delete();
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETED ===\n";