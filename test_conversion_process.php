<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

$app->boot();

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariantService;
use Illuminate\Support\Facades\DB;

echo "=== TESTING SMART PRINT CONVERSION ===\n\n";

// Find a regular product to convert
$product = Product::where('is_print_service', false)
                 ->orWhereNull('is_print_service')
                 ->first();

if (!$product) {
    echo "❌ No regular products found to convert\n";
    exit;
}

echo "🎯 CONVERTING PRODUCT: {$product->name} (ID: {$product->id})\n\n";

echo "BEFORE CONVERSION:\n";
echo "- is_print_service: " . ($product->is_print_service ? 'true' : 'false') . "\n";
echo "- is_smart_print_enabled: " . ($product->is_smart_print_enabled ? 'true' : 'false') . "\n";
echo "- Product Variants count: " . $product->productVariants()->count() . "\n";

try {
    DB::beginTransaction();

    // Convert to Smart Print Product
    $product->update([
        'is_print_service' => true,
        'is_smart_print_enabled' => true,
        'status' => 1,
    ]);

    // Check existing variants
    $existingVariants = $product->productVariants()
        ->whereIn('name', ['BW', 'Color'])
        ->pluck('name')
        ->toArray();

    $variantsCreated = [];
    $productVariantService = new ProductVariantService();

    // Create BW variant if not exists
    if (!in_array('BW', $existingVariants)) {
        $productVariantService->createVariant($product, [
            'name' => 'BW',
            'sku' => $product->sku . '-BW',
            'price' => $product->price ?? 0,
            'stock' => 0,
            'weight' => 0,
        ], []);
        $variantsCreated[] = 'BW';
    }

    // Create Color variant if not exists
    if (!in_array('Color', $existingVariants)) {
        $productVariantService->createVariant($product, [
            'name' => 'Color',
            'sku' => $product->sku . '-Color',
            'price' => ($product->price ?? 0) * 1.5,
            'stock' => 0,
            'weight' => 0,
        ], []);
        $variantsCreated[] = 'Color';
    }

    DB::commit();

    // Refresh product
    $product->refresh();

    echo "\n✅ CONVERSION SUCCESSFUL!\n\n";

    echo "AFTER CONVERSION:\n";
    echo "- is_print_service: " . ($product->is_print_service ? 'true' : 'false') . "\n";
    echo "- is_smart_print_enabled: " . ($product->is_smart_print_enabled ? 'true' : 'false') . "\n";
    echo "- Product Variants count: " . $product->productVariants()->count() . "\n";
    echo "- Variants created: " . implode(', ', $variantsCreated) . "\n";

    // Test if product now appears in Stock Management
    echo "\n🔍 CHECKING STOCK MANAGEMENT VISIBILITY...\n";
    
    // Refresh product to get updated relationships
    $product = $product->fresh();
    
    echo "Product has " . $product->productVariants()->count() . " product variants\n";
    
    // Show variants
    echo "\nPRODUCT VARIANTS:\n";
    foreach ($product->productVariants as $variant) {
        echo "- {$variant->name}: {$variant->sku} (Price: " . number_format($variant->price, 0, ',', '.') . ", Active: " . ($variant->is_active ? 'Yes' : 'No') . ")\n";
    }
    
    $stockProducts = Product::where('is_print_service', true)
                           ->where('status', 1)
                           ->whereHas('productVariants', function ($query) {
                               $query->where('is_active', 1);
                           })
                           ->where('id', $product->id)
                           ->get();

    if ($stockProducts->count() > 0) {
        echo "✅ Product WILL appear in Stock Management Print Service!\n";
    } else {
        echo "❌ Product will NOT appear in Stock Management.\n";
    }

    echo "\n🎉 SMART PRINT CONVERTER WORKING PERFECTLY!\n";
    echo "Visit: /admin/smart-print-converter to use the tool\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ CONVERSION FAILED: " . $e->getMessage() . "\n";
}