<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StockManagementService;

echo "=== COMPREHENSIVE DEBUG: KERTAS FOTO ===\n\n";

// Cari produk kertas foto yang baru dibuat
$fotoProduct = Product::where('name', 'LIKE', '%foto%')
                     ->orWhere('name', 'LIKE', '%Foto%')
                     ->orderBy('id', 'desc')
                     ->first();

if (!$fotoProduct) {
    echo "❌ Produk kertas foto tidak ditemukan\n";
    echo "Mencari 10 produk terbaru...\n\n";
    
    $latestProducts = Product::orderBy('id', 'desc')->limit(10)->get();
    foreach ($latestProducts as $product) {
        echo "- ID {$product->id}: {$product->name} (created: {$product->created_at})\n";
    }
    exit;
}

echo "✅ Produk Ditemukan: {$fotoProduct->name} (ID: {$fotoProduct->id})\n\n";

echo "=== STEP 1: BASIC PRODUCT INFO ===\n";
echo "- Name: {$fotoProduct->name}\n";
echo "- SKU: {$fotoProduct->sku}\n";
echo "- Type: {$fotoProduct->type}\n";
echo "- Status: {$fotoProduct->status}\n";
echo "- Created: {$fotoProduct->created_at}\n";
echo "- Brand ID: {$fotoProduct->brand_id}\n";
echo "- User ID: {$fotoProduct->user_id}\n\n";

echo "=== STEP 2: CHECKBOX VALUES (RAW) ===\n";
echo "- is_print_service: {$fotoProduct->is_print_service} (type: " . gettype($fotoProduct->is_print_service) . ")\n";
echo "- is_smart_print_enabled: {$fotoProduct->is_smart_print_enabled} (type: " . gettype($fotoProduct->is_smart_print_enabled) . ")\n";
echo "- Raw DB values:\n";
$rawData = DB::table('products')->where('id', $fotoProduct->id)->first();
echo "  * is_print_service (DB): {$rawData->is_print_service}\n";
echo "  * is_smart_print_enabled (DB): {$rawData->is_smart_print_enabled}\n\n";

echo "=== STEP 3: VARIANTS ANALYSIS ===\n";
$variants = ProductVariant::where('product_id', $fotoProduct->id)->get();
echo "Total variants: " . $variants->count() . "\n";

if ($variants->count() > 0) {
    foreach ($variants as $variant) {
        echo "- Variant ID {$variant->id}:\n";
        echo "  * Name: {$variant->name}\n";
        echo "  * SKU: {$variant->sku}\n";
        echo "  * is_active: {$variant->is_active} (type: " . gettype($variant->is_active) . ")\n";
        echo "  * stock: {$variant->stock}\n";
        echo "  * print_type: {$variant->print_type}\n\n";
    }
} else {
    echo "❌ NO VARIANTS FOUND!\n\n";
}

echo "=== STEP 4: STOCK MANAGEMENT SERVICE TEST ===\n";
$stockService = new StockManagementService();

try {
    $allStockVariants = $stockService->getVariantsByStock();
    echo "Total variants in stock management: " . $allStockVariants->count() . "\n";
    
    $fotoVariants = $allStockVariants->filter(function($variant) use ($fotoProduct) {
        return $variant->product && $variant->product->id == $fotoProduct->id;
    });
    
    echo "Variants from this product in stock: " . $fotoVariants->count() . "\n\n";
    
    if ($fotoVariants->count() > 0) {
        echo "✅ FOUND IN STOCK MANAGEMENT:\n";
        foreach ($fotoVariants as $variant) {
            echo "- {$variant->name} (Stock: {$variant->stock})\n";
        }
    } else {
        echo "❌ NOT FOUND IN STOCK MANAGEMENT\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR in StockManagementService: " . $e->getMessage() . "\n";
}

echo "\n=== STEP 5: DETAILED FILTER ANALYSIS ===\n";

// Manual filter check
echo "Checking each filter criteria:\n";

echo "1. product.is_print_service = true: ";
$criterion1 = ($fotoProduct->is_print_service == true);
echo ($criterion1 ? "✅ PASS" : "❌ FAIL") . " (value: {$fotoProduct->is_print_service})\n";

echo "2. product.status = 1: ";
$criterion2 = ($fotoProduct->status == 1);
echo ($criterion2 ? "✅ PASS" : "❌ FAIL") . " (value: {$fotoProduct->status})\n";

echo "3. variant.is_active = true: ";
$activeVariants = ProductVariant::where('product_id', $fotoProduct->id)->where('is_active', true)->get();
$criterion3 = ($activeVariants->count() > 0);
echo ($criterion3 ? "✅ PASS" : "❌ FAIL") . " (count: {$activeVariants->count()})\n";

$allCriteriaMet = $criterion1 && $criterion2 && $criterion3;
echo "\nAll criteria met: " . ($allCriteriaMet ? "✅ YES" : "❌ NO") . "\n";

if (!$allCriteriaMet) {
    echo "\n=== PROBLEM DIAGNOSIS ===\n";
    if (!$criterion1) {
        echo "❌ MAIN ISSUE: is_print_service is not true\n";
        echo "   This means checkbox was not saved properly\n";
    }
    if (!$criterion2) {
        echo "❌ ISSUE: Product status is not active\n";
    }
    if (!$criterion3) {
        echo "❌ ISSUE: No active variants\n";
        if ($variants->count() == 0) {
            echo "   No variants were created at all\n";
        } else {
            echo "   Variants exist but are not active\n";
        }
    }
}

echo "\n=== STEP 6: FULL CONTROLLER/SERVICE TRACE ===\n";

// Check if ProductVariantService was used
echo "Checking creation method...\n";
if ($fotoProduct->created_at) {
    echo "Product was created via ProductVariantService::createBaseProduct()\n";
    
    // Re-create the scenario
    echo "Simulating what should have happened:\n";
    
    $simulatedData = [
        'is_print_service' => true,
        'is_smart_print_enabled' => true,
    ];
    
    echo "If checkbox handling worked:\n";
    echo "- Should save is_print_service = true\n";
    echo "- Should save is_smart_print_enabled = true\n";
    echo "- Should auto-create variants\n";
    
    echo "\nActual result:\n";
    echo "- Saved is_print_service = {$fotoProduct->is_print_service}\n";
    echo "- Saved is_smart_print_enabled = {$fotoProduct->is_smart_print_enabled}\n";
    echo "- Created variants = {$variants->count()}\n";
}

echo "\n=== RECOMMENDATIONS ===\n";
echo "Based on analysis, the issues are:\n";

if (!$criterion1) {
    echo "1. ❌ Checkbox values not being saved in ProductVariantService\n";
    echo "   → Need to verify ProductVariantService::createBaseProduct includes checkbox fields\n";
}

if (!$criterion3 && $variants->count() == 0) {
    echo "2. ❌ Auto-create variants not working\n";
    echo "   → Need to check createDefaultSmartPrintVariants in ProductController\n";
}

if (!$criterion3 && $variants->count() > 0) {
    echo "2. ❌ Variants exist but not active\n";
    echo "   → Need to check is_active field in variant creation\n";
}

echo "\n=== END DIAGNOSIS ===\n";