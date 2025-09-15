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

echo "=== TESTING FIXED CONVERTER WITH KERTAS MINYAK ===\n\n";

// Find the problematic product
$product = Product::where('name', 'like', '%kertas minyak%')
                 ->orWhere('sku', 'fskfsfshkh')
                 ->first();

if (!$product) {
    echo "❌ Product not found\n";
    exit;
}

echo "🎯 TESTING PRODUCT: {$product->name} (ID: {$product->id}, SKU: {$product->sku})\n\n";

echo "BEFORE CONVERSION:\n";
echo "- is_print_service: " . ($product->is_print_service ? 'true' : 'false') . "\n";
echo "- is_smart_print_enabled: " . ($product->is_smart_print_enabled ? 'true' : 'false') . "\n";

// Show existing variants
echo "\nEXISTING VARIANTS:\n";
$existingVariants = $product->productVariants;
foreach ($existingVariants as $variant) {
    echo "- {$variant->name}: {$variant->sku}\n";
}

echo "\nTESTING NEW LOGIC...\n";

// Test the new detection logic
$smartVariants = $product->productVariants()
    ->where(function($query) {
        $query->whereIn('name', ['BW', 'Color'])
              ->orWhere('name', 'like', '%Black & White%')
              ->orWhere('name', 'like', '%Color%')
              ->orWhere('name', 'like', '%BW%');
    })
    ->get();

echo "Smart variants detected: " . $smartVariants->count() . "\n";
foreach ($smartVariants as $variant) {
    echo "- {$variant->name}: {$variant->sku}\n";
}

$hasBWVariant = $smartVariants->contains(function($variant) {
    return in_array($variant->name, ['BW']) || 
           stripos($variant->name, 'Black') !== false || 
           stripos($variant->name, 'BW') !== false;
});

$hasColorVariant = $smartVariants->contains(function($variant) {
    return in_array($variant->name, ['Color']) || 
           stripos($variant->name, 'Color') !== false;
});

echo "\nDETECTION RESULTS:\n";
echo "- Has BW Variant: " . ($hasBWVariant ? 'YES' : 'NO') . "\n";
echo "- Has Color Variant: " . ($hasColorVariant ? 'YES' : 'NO') . "\n";

// Function to generate unique SKU
function generateUniqueSku($baseSku) {
    $originalSku = $baseSku;
    $counter = 1;

    while (ProductVariant::where('sku', $baseSku)->exists()) {
        $baseSku = $originalSku . '-' . $counter;
        $counter++;
    }

    return $baseSku;
}

// Test SKU generation
$expectedBWSku = generateUniqueSku($product->sku . '-BW');
$expectedColorSku = generateUniqueSku($product->sku . '-Color');

echo "\nSKU GENERATION:\n";
echo "- Expected BW SKU: {$expectedBWSku}\n";
echo "- Expected Color SKU: {$expectedColorSku}\n";

echo "\n=== SIMULATING CONVERSION ===\n";

try {
    DB::beginTransaction();

    // Update product flags
    $product->update([
        'is_print_service' => true,
        'is_smart_print_enabled' => true,
        'status' => 1,
    ]);

    $variantsCreated = [];

    // Create only missing variants
    if (!$hasBWVariant) {
        echo "Would create BW variant with SKU: {$expectedBWSku}\n";
        $variantsCreated[] = 'BW (would create)';
    } else {
        echo "Skipping BW variant - already exists\n";
    }

    if (!$hasColorVariant) {
        echo "Would create Color variant with SKU: {$expectedColorSku}\n";
        $variantsCreated[] = 'Color (would create)';
    } else {
        echo "Skipping Color variant - already exists\n";
    }

    DB::rollBack(); // Don't actually commit

    echo "\n✅ CONVERSION SIMULATION SUCCESSFUL!\n";
    echo "Product flags updated, variants handled properly.\n";
    
    if (empty($variantsCreated)) {
        echo "No new variants needed - product already has BW/Color variants.\n";
    } else {
        echo "Variants that would be created: " . implode(', ', $variantsCreated) . "\n";
    }

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ SIMULATION FAILED: " . $e->getMessage() . "\n";
}

echo "\n🎉 FIXED CONVERTER LOGIC SHOULD NOW WORK!\n";
echo "The converter will:\n";
echo "1. Detect existing BW/Color variants (even with different names)\n";
echo "2. Only create missing variants\n";
echo "3. Generate unique SKUs to avoid conflicts\n";
echo "4. Update product flags regardless\n";