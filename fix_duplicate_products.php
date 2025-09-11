<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PrintOrder;
use App\Services\StockManagementService;
use Illuminate\Support\Facades\DB;

echo "=== FIXING DUPLICATE PRODUCTS IN STOCK MANAGEMENT ===\n\n";

echo "1. ANALYZING CURRENT SITUATION...\n";

$printProducts = Product::where('is_print_service', true)->with('activeVariants')->get();
echo "Found " . $printProducts->count() . " print service products\n\n";

foreach ($printProducts as $product) {
    echo "Product ID {$product->id}: {$product->name}\n";
    echo "  Variants: " . $product->activeVariants->count() . "\n";
    echo "  Total orders: " . PrintOrder::whereIn('paper_variant_id', $product->activeVariants->pluck('id'))->count() . "\n\n";
}

echo "2. CONSOLIDATING STOCK DATA...\n";

$consolidationPlan = [
    'A4_bw' => ['keep' => 45, 'remove' => 57, 'reason' => 'Keep higher stock (11985 vs 9999)'],
    'A4_color' => ['keep' => 46, 'remove' => 58, 'reason' => 'Keep same stock, no orders on variant 58'],
    'F4_bw' => ['keep' => 49, 'remove' => 61, 'reason' => 'Keep same stock, no orders on variant 61'],
    'F4_color' => ['keep' => 50, 'remove' => 62, 'reason' => 'Keep same stock, no orders on variant 62'],
    'A3_bw' => ['keep' => 47, 'remove' => 59, 'reason' => 'Keep same stock, no orders on variant 59'],
    'A3_color' => ['keep' => 60, 'remove' => 48, 'reason' => 'Keep higher stock (2000 vs 1997)']
];

DB::beginTransaction();

try {
    foreach ($consolidationPlan as $combination => $plan) {
        $keepVariant = ProductVariant::find($plan['keep']);
        $removeVariant = ProductVariant::find($plan['remove']);
        
        echo "Processing {$combination}:\n";
        echo "  Keep: Variant ID {$plan['keep']} (Stock: {$keepVariant->stock})\n";
        echo "  Remove: Variant ID {$plan['remove']} (Stock: {$removeVariant->stock})\n";
        echo "  Reason: {$plan['reason']}\n";
        
        $ordersToUpdate = PrintOrder::where('paper_variant_id', $plan['remove'])->get();
        
        if ($ordersToUpdate->count() > 0) {
            echo "  Found {$ordersToUpdate->count()} orders to transfer\n";
            
            foreach ($ordersToUpdate as $order) {
                $order->update(['paper_variant_id' => $plan['keep']]);
                echo "    Transferred order {$order->order_code} to variant {$plan['keep']}\n";
            }
        }
        
        $combinedStock = $keepVariant->stock + $removeVariant->stock;
        $keepVariant->update(['stock' => $combinedStock]);
        echo "  Combined stock: {$combinedStock} sheets\n";
        
        $removeVariant->update(['is_active' => false]);
        echo "  Deactivated variant {$plan['remove']}\n\n";
    }
    
    echo "3. REMOVING DUPLICATE PRODUCT...\n";
    
    $product137 = Product::find(137);
    if ($product137) {
        $product137->update(['status' => 0]);
        echo "Deactivated duplicate product ID 137\n";
    }
    
    echo "4. UPDATING STOCK MANAGEMENT SERVICE...\n";
    
    DB::commit();
    echo "✅ CONSOLIDATION COMPLETED SUCCESSFULLY\n\n";
    
} catch (\Exception $e) {
    DB::rollback();
    echo "❌ ERROR DURING CONSOLIDATION: " . $e->getMessage() . "\n";
    exit(1);
}

echo "5. VERIFYING RESULTS...\n";

$stockService = new StockManagementService();
$finalVariants = $stockService->getVariantsByStock('asc');

echo "Final admin stock management data:\n";
$finalGrouped = [];
foreach ($finalVariants as $variant) {
    $key = $variant->paper_size . '_' . $variant->print_type;
    if (!isset($finalGrouped[$key])) {
        $finalGrouped[$key] = [];
    }
    $finalGrouped[$key][] = $variant;
}

$duplicatesRemaining = false;
foreach ($finalGrouped as $key => $variants) {
    if (count($variants) > 1) {
        echo "❌ STILL DUPLICATE: {$key} - " . count($variants) . " variants\n";
        $duplicatesRemaining = true;
    } else {
        echo "✅ {$key}: Variant ID {$variants[0]->id} - {$variants[0]->stock} stock\n";
    }
}

if (!$duplicatesRemaining) {
    echo "\n🎉 ALL DUPLICATES SUCCESSFULLY RESOLVED!\n";
} else {
    echo "\n⚠️ Some duplicates still remain - manual investigation needed\n";
}

echo "\n6. FINAL VERIFICATION WITH FRONTEND...\n";

use App\Services\PrintService;
$printService = new PrintService();

$frontendProducts = $printService->getPrintProducts();
echo "Frontend shows " . $frontendProducts->count() . " active print service products\n";

foreach ($frontendProducts as $product) {
    echo "Product: {$product->name} (ID: {$product->id})\n";
    echo "Active variants: " . $product->activeVariants->count() . "\n";
    
    foreach ($product->activeVariants as $variant) {
        echo "  - {$variant->paper_size} {$variant->print_type}: {$variant->stock} stock\n";
    }
    echo "\n";
}

echo "=== DUPLICATE RESOLUTION COMPLETE ===\n";
