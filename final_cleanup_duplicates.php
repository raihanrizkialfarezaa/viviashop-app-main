<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductVariant;
use App\Models\PrintOrder;
use Illuminate\Support\Facades\DB;

echo "=== FINAL CLEANUP OF A3 COLOR DUPLICATES ===\n\n";

echo "1. CHECKING A3 COLOR VARIANTS...\n";

$a3ColorVariants = ProductVariant::where('paper_size', 'A3')
    ->where('print_type', 'color')
    ->where('is_active', true)
    ->get();

echo "Found " . $a3ColorVariants->count() . " active A3 color variants:\n";
foreach ($a3ColorVariants as $variant) {
    echo "- ID {$variant->id}: Product {$variant->product_id}, Stock: {$variant->stock}\n";
    
    $orderCount = PrintOrder::where('paper_variant_id', $variant->id)->count();
    echo "  Orders: {$orderCount}\n";
}

echo "\n2. CONSOLIDATING TO SINGLE VARIANT...\n";

if ($a3ColorVariants->count() > 1) {
    $variant48 = $a3ColorVariants->where('id', 48)->first();
    $variant60 = $a3ColorVariants->where('id', 60)->first();
    
    if ($variant48 && $variant60) {
        echo "Consolidating variants 48 and 60:\n";
        
        $orders48 = PrintOrder::where('paper_variant_id', 48)->count();
        $orders60 = PrintOrder::where('paper_variant_id', 60)->count();
        
        echo "- Variant 48: {$variant48->stock} stock, {$orders48} orders\n";
        echo "- Variant 60: {$variant60->stock} stock, {$orders60} orders\n";
        
        DB::beginTransaction();
        try {
            $combinedStock = $variant48->stock + $variant60->stock;
            
            PrintOrder::where('paper_variant_id', 48)->update(['paper_variant_id' => 60]);
            echo "Transferred {$orders48} orders from variant 48 to variant 60\n";
            
            $variant60->update(['stock' => $combinedStock]);
            echo "Updated variant 60 stock to {$combinedStock}\n";
            
            $variant48->update(['is_active' => false]);
            echo "Deactivated variant 48\n";
            
            DB::commit();
            echo "✅ CONSOLIDATION COMPLETED\n";
            
        } catch (\Exception $e) {
            DB::rollback();
            echo "❌ ERROR: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n3. FINAL VERIFICATION...\n";

use App\Services\StockManagementService;
$stockService = new StockManagementService();
$finalVariants = $stockService->getVariantsByStock('asc');

echo "Final admin stock management data:\n";
$groupedVariants = [];
foreach ($finalVariants as $variant) {
    $key = $variant->paper_size . '_' . $variant->print_type;
    if (!isset($groupedVariants[$key])) {
        $groupedVariants[$key] = [];
    }
    $groupedVariants[$key][] = $variant;
}

$hasDuplicates = false;
foreach ($groupedVariants as $key => $variants) {
    if (count($variants) > 1) {
        echo "❌ DUPLICATE: {$key} - " . count($variants) . " variants\n";
        $hasDuplicates = true;
    } else {
        echo "✅ {$key}: Variant ID {$variants[0]->id} - {$variants[0]->stock} stock\n";
    }
}

if (!$hasDuplicates) {
    echo "\n🎉 NO MORE DUPLICATES IN ADMIN STOCK MANAGEMENT!\n";
} else {
    echo "\n⚠️ Some duplicates still remain\n";
}

echo "\n4. FRONTEND CONSISTENCY CHECK...\n";

use App\Services\PrintService;
$printService = new PrintService();

$frontendProducts = $printService->getPrintProducts();
$product = $frontendProducts->first();

echo "Frontend variants (after deduplication):\n";

$responseData = [
    'success' => true,
    'products' => $frontendProducts->map(function($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'variants' => $product->activeVariants->map(function($variant) {
                return [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'price' => $variant->price,
                    'print_type' => $variant->print_type,
                    'paper_size' => $variant->paper_size,
                    'stock' => $variant->stock
                ];
            })
        ];
    })->toArray()
];

$variantMap = new \Illuminate\Support\Collection();
foreach ($responseData['products'] as $product) {
    foreach ($product['variants'] as $variant) {
        $key = $variant['paper_size'] . '_' . $variant['print_type'];
        
        if (!$variantMap->has($key)) {
            $variantMap->put($key, $variant);
        } else {
            $existing = $variantMap->get($key);
            if ($variant['stock'] > $existing['stock']) {
                $variantMap->put($key, $variant);
            }
        }
    }
}

foreach ($variantMap as $key => $variant) {
    echo "- {$variant['paper_size']} {$variant['print_type']}: {$variant['stock']} stock (ID: {$variant['id']})\n";
}

echo "\n=== DUPLICATE CLEANUP COMPLETE ===\n";
