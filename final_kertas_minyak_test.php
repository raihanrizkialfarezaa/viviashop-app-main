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

echo "=== FINAL TEST: CONVERT KERTAS MINYAK ===\n\n";

// Find the problematic product
$product = Product::where('name', 'like', '%kertas minyak%')
                 ->orWhere('sku', 'fskfsfshkh')
                 ->first();

if (!$product) {
    echo "❌ Product not found\n";
    exit;
}

echo "🎯 CONVERTING PRODUCT: {$product->name} (ID: {$product->id}, SKU: {$product->sku})\n\n";

// Function to generate unique SKU (same as in controller)
function generateUniqueSku($baseSku) {
    $originalSku = $baseSku;
    $counter = 1;

    while (ProductVariant::where('sku', $baseSku)->exists()) {
        $baseSku = $originalSku . '-' . $counter;
        $counter++;
    }

    return $baseSku;
}

try {
    DB::beginTransaction();

    // Store original values for logging
    $originalPrintService = $product->is_print_service;
    $originalSmartPrint = $product->is_smart_print_enabled;

    echo "BEFORE CONVERSION:\n";
    echo "- is_print_service: " . ($originalPrintService ? 'true' : 'false') . "\n";
    echo "- is_smart_print_enabled: " . ($originalSmartPrint ? 'true' : 'false') . "\n";

    // Convert to Smart Print Product
    $product->update([
        'is_print_service' => true,
        'is_smart_print_enabled' => true,
        'status' => 1, // Ensure product is active
    ]);

    // Check if BW and Color variants exist (including by SKU and similar names)
    $existingVariants = $product->productVariants()
        ->where(function($query) {
            $query->whereIn('name', ['BW', 'Color'])
                  ->orWhere('name', 'like', '%Black & White%')
                  ->orWhere('name', 'like', '%Color%')
                  ->orWhere('name', 'like', '%BW%');
        })
        ->get();

    // Check what we actually have
    $hasBWVariant = $existingVariants->contains(function($variant) {
        return in_array($variant->name, ['BW']) || 
               stripos($variant->name, 'Black') !== false || 
               stripos($variant->name, 'BW') !== false;
    });

    $hasColorVariant = $existingVariants->contains(function($variant) {
        return in_array($variant->name, ['Color']) || 
               stripos($variant->name, 'Color') !== false;
    });

    echo "\nEXISTING VARIANTS DETECTION:\n";
    echo "- Has BW Variant: " . ($hasBWVariant ? 'YES' : 'NO') . "\n";
    echo "- Has Color Variant: " . ($hasColorVariant ? 'YES' : 'NO') . "\n";

    // Also check for SKU conflicts
    $expectedBWSku = generateUniqueSku($product->sku . '-BW');
    $expectedColorSku = generateUniqueSku($product->sku . '-Color');

    $variantsCreated = [];
    $productVariantService = new ProductVariantService();

    // Create BW variant if not exists
    if (!$hasBWVariant) {
        echo "Creating BW variant with SKU: {$expectedBWSku}\n";
        $productVariantService->createVariant($product, [
            'name' => 'BW',
            'sku' => $expectedBWSku,
            'price' => $product->price ?? 0,
            'stock' => 0,
            'weight' => 0,
        ], []); // Empty attributes array
        $variantsCreated[] = 'BW';
    } else {
        echo "Skipping BW variant - already exists\n";
    }

    // Create Color variant if not exists
    if (!$hasColorVariant) {
        echo "Creating Color variant with SKU: {$expectedColorSku}\n";
        $productVariantService->createVariant($product, [
            'name' => 'Color',
            'sku' => $expectedColorSku,
            'price' => ($product->price ?? 0) * 1.5, // Color variant 1.5x price
            'stock' => 0,
            'weight' => 0,
        ], []); // Empty attributes array
        $variantsCreated[] = 'Color';
    } else {
        echo "Skipping Color variant - already exists\n";
    }

    DB::commit();

    // Refresh product
    $product = $product->fresh();

    echo "\n✅ CONVERSION SUCCESSFUL!\n\n";

    echo "AFTER CONVERSION:\n";
    echo "- is_print_service: " . ($product->is_print_service ? 'true' : 'false') . "\n";
    echo "- is_smart_print_enabled: " . ($product->is_smart_print_enabled ? 'true' : 'false') . "\n";

    if (!empty($variantsCreated)) {
        echo "- Variants created: " . implode(', ', $variantsCreated) . "\n";
    } else {
        echo "- No new variants created (already existed)\n";
    }

    // Show all variants
    echo "\nALL PRODUCT VARIANTS:\n";
    foreach ($product->productVariants as $variant) {
        echo "- {$variant->name}: {$variant->sku} (Price: " . number_format($variant->price, 0, ',', '.') . ", Active: " . ($variant->is_active ? 'Yes' : 'No') . ")\n";
    }

    // Test if product now appears in Stock Management
    echo "\n🔍 CHECKING STOCK MANAGEMENT VISIBILITY...\n";
    
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

    echo "\n🎉 CONVERSION COMPLETED SUCCESSFULLY!\n";
    echo "Now try the same conversion in the browser - it should work without errors.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ CONVERSION FAILED: " . $e->getMessage() . "\n";
}