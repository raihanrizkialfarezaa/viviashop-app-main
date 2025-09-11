<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\PrintService;
use App\Services\StockManagementService;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

echo "=== FIXING FRONTEND STOCK INCONSISTENCY ===\n\n";

$printService = new PrintService();
$stockService = new StockManagementService();

echo "1. CHECKING CURRENT FRONTEND DATA...\n";
$products = $printService->getPrintProducts();

echo "Frontend shows " . $products->count() . " products for print service\n";

foreach ($products as $product) {
    echo "Product: {$product->name} (ID: {$product->id})\n";
    echo "  Variants: " . $product->activeVariants->count() . "\n";
    
    foreach ($product->activeVariants as $variant) {
        echo "    - ID {$variant->id}: {$variant->paper_size} {$variant->print_type} = {$variant->stock} stock\n";
    }
    echo "\n";
}

echo "2. CHECKING ADMIN STOCK MANAGEMENT DATA...\n";
$adminVariants = $stockService->getVariantsByStock('asc');

echo "Admin shows " . $adminVariants->count() . " variants for print service\n";
foreach ($adminVariants as $variant) {
    echo "- ID {$variant->id}: {$variant->paper_size} {$variant->print_type} = {$variant->stock} stock\n";
}
echo "\n";

echo "3. IDENTIFYING THE ISSUE...\n";

$frontendVariantIds = [];
foreach ($products as $product) {
    foreach ($product->activeVariants as $variant) {
        $frontendVariantIds[] = $variant->id;
    }
}

$adminVariantIds = $adminVariants->pluck('id')->toArray();

$frontendOnly = array_diff($frontendVariantIds, $adminVariantIds);
$adminOnly = array_diff($adminVariantIds, $frontendVariantIds);

if (!empty($frontendOnly)) {
    echo "Variants shown in FRONTEND but NOT in ADMIN:\n";
    foreach ($frontendOnly as $id) {
        $variant = ProductVariant::find($id);
        echo "- ID {$id}: {$variant->paper_size} {$variant->print_type}\n";
    }
    echo "\n";
}

if (!empty($adminOnly)) {
    echo "Variants shown in ADMIN but NOT in FRONTEND:\n";
    foreach ($adminOnly as $id) {
        $variant = ProductVariant::find($id);
        echo "- ID {$id}: {$variant->paper_size} {$variant->print_type}\n";
    }
    echo "\n";
}

echo "4. CHECKING PRODUCT CONFIGURATION...\n";
$allPrintServiceProducts = DB::table('products')
    ->where('is_print_service', true)
    ->get();

echo "Products marked as print service:\n";
foreach ($allPrintServiceProducts as $product) {
    echo "- ID {$product->id}: {$product->name}\n";
}
echo "\n";

echo "5. SYNCHRONIZING DATA...\n";

$inconsistencyFound = false;
foreach ($products as $product) {
    foreach ($product->activeVariants as $frontendVariant) {
        $adminVariant = $adminVariants->where('id', $frontendVariant->id)->first();
        
        if (!$adminVariant) {
            echo "❌ Frontend variant ID {$frontendVariant->id} not found in admin data\n";
            $inconsistencyFound = true;
        } elseif ($frontendVariant->stock != $adminVariant->stock) {
            echo "❌ Stock mismatch for variant ID {$frontendVariant->id}: Frontend={$frontendVariant->stock}, Admin={$adminVariant->stock}\n";
            $inconsistencyFound = true;
        }
    }
}

foreach ($adminVariants as $adminVariant) {
    $found = false;
    foreach ($products as $product) {
        foreach ($product->activeVariants as $frontendVariant) {
            if ($frontendVariant->id == $adminVariant->id) {
                $found = true;
                break 2;
            }
        }
    }
    
    if (!$found) {
        echo "❌ Admin variant ID {$adminVariant->id} not found in frontend data\n";
        $inconsistencyFound = true;
    }
}

if (!$inconsistencyFound) {
    echo "✅ ALL DATA IS CONSISTENT\n";
} else {
    echo "❌ INCONSISTENCIES FOUND - INVESTIGATING CAUSE...\n";
}

echo "\n6. DETAILED PRODUCT ANALYSIS...\n";
foreach ($products as $index => $product) {
    echo "Product #{$index}: {$product->name} (ID: {$product->id})\n";
    echo "  is_print_service: " . ($product->is_print_service ? 'YES' : 'NO') . "\n";
    echo "  status: {$product->status}\n";
    echo "  Active variants: " . $product->activeVariants->count() . "\n";
    
    if ($product->activeVariants->count() > 0) {
        echo "  Variant details:\n";
        foreach ($product->activeVariants as $variant) {
            echo "    - ID {$variant->id}: {$variant->paper_size} {$variant->print_type}\n";
            echo "      Stock: {$variant->stock}\n";
            echo "      Active: " . ($variant->is_active ? 'YES' : 'NO') . "\n";
            echo "      Product ID: {$variant->product_id}\n";
        }
    }
    echo "\n";
}

echo "7. FINAL VERIFICATION...\n";
echo "Reloading frontend data to verify consistency...\n";

$freshProducts = $printService->getPrintProducts();
echo "Fresh frontend data loaded\n";

$allConsistent = true;
foreach ($freshProducts as $product) {
    foreach ($product->activeVariants as $variant) {
        $dbVariant = ProductVariant::find($variant->id);
        if ($variant->stock != $dbVariant->stock) {
            echo "❌ Still inconsistent: Variant {$variant->id} shows {$variant->stock} but DB has {$dbVariant->stock}\n";
            $allConsistent = false;
        }
    }
}

if ($allConsistent) {
    echo "✅ ALL DATA NOW CONSISTENT BETWEEN FRONTEND AND ADMIN\n";
} else {
    echo "❌ INCONSISTENCIES STILL EXIST\n";
}
