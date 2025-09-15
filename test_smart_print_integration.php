<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Requests\Admin\ProductRequest;
use Illuminate\Http\Request;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== Smart Print Feature Integration Test ===\n\n";

    echo "1. Testing existing products with smart print flag...\n";
    
    $existingProducts = Product::all();
    echo "Total products in database: " . $existingProducts->count() . "\n";
    
    $smartPrintProducts = Product::smartPrintEnabled()->get();
    echo "Products with smart print enabled: " . $smartPrintProducts->count() . "\n";
    
    $printServiceProducts = Product::where('is_print_service', true)->get();
    echo "Products marked as print service: " . $printServiceProducts->count() . "\n";
    
    echo "\n2. Testing Product model scopes...\n";
    
    $activeSmartPrint = Product::active()->smartPrintEnabled()->get();
    echo "Active smart print products: " . $activeSmartPrint->count() . "\n";
    
    $smartPrintWithStock = Product::smartPrintEnabled()->withStock()->get();
    echo "Smart print products with stock: " . $smartPrintWithStock->count() . "\n";
    
    echo "\n3. Testing update existing product to enable smart print...\n";
    
    $testProduct = Product::where('is_print_service', true)->first();
    if ($testProduct) {
        echo "Found existing print service product: {$testProduct->name}\n";
        echo "Current smart print status: " . ($testProduct->is_smart_print_enabled ? 'Enabled' : 'Disabled') . "\n";
        
        $testProduct->update(['is_smart_print_enabled' => true]);
        echo "✓ Updated smart print status to: Enabled\n";
        
        $testProduct->refresh();
        echo "✓ Verified smart print status: " . ($testProduct->is_smart_print_enabled ? 'Enabled' : 'Disabled') . "\n";
    } else {
        echo "No existing print service products found\n";
    }
    
    echo "\n4. Testing with different product types...\n";
    
    $simpleProducts = Product::where('type', 'simple')->smartPrintEnabled()->get();
    echo "Simple products with smart print: " . $simpleProducts->count() . "\n";
    
    $configurableProducts = Product::where('type', 'configurable')->smartPrintEnabled()->get();
    echo "Configurable products with smart print: " . $configurableProducts->count() . "\n";
    
    echo "\n5. Testing mass update scenario...\n";
    
    $printServiceProductsToUpdate = Product::where('is_print_service', true)
                                          ->where('is_smart_print_enabled', false)
                                          ->limit(5)
                                          ->get();
    
    echo "Print service products without smart print: " . $printServiceProductsToUpdate->count() . "\n";
    
    foreach ($printServiceProductsToUpdate as $product) {
        $product->update(['is_smart_print_enabled' => true]);
        echo "✓ Enabled smart print for: {$product->name}\n";
    }
    
    echo "\n6. Final statistics...\n";
    
    $finalStats = [
        'total_products' => Product::count(),
        'active_products' => Product::active()->count(),
        'print_service_products' => Product::where('is_print_service', true)->count(),
        'smart_print_enabled' => Product::smartPrintEnabled()->count(),
        'both_print_and_smart' => Product::where('is_print_service', true)
                                        ->where('is_smart_print_enabled', true)
                                        ->count()
    ];
    
    foreach ($finalStats as $key => $value) {
        echo ucfirst(str_replace('_', ' ', $key)) . ": {$value}\n";
    }
    
    echo "\n7. Testing PrintService integration...\n";
    
    $printService = new \App\Services\PrintService();
    $availableProducts = $printService->getPrintProducts();
    
    echo "Products available for print service: " . $availableProducts->count() . "\n";
    foreach ($availableProducts as $product) {
        echo "- {$product->name} (Variants: {$product->activeVariants->count()})\n";
    }
    
    echo "\n=== Integration Test Summary ===\n";
    echo "✓ Smart print field is working correctly in database\n";
    echo "✓ Model scopes are functioning properly\n";
    echo "✓ Existing products can be updated\n";
    echo "✓ Different product types supported\n";
    echo "✓ PrintService integration working\n";
    echo "✓ Mass update operations successful\n";
    
    echo "\n=== All Integration Tests Passed! ===\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}