<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StockManagementService;
use Illuminate\Support\Facades\DB;

echo "=== ANALYZING DUPLICATE PRODUCTS IN ADMIN STOCK MANAGEMENT ===\n\n";

$stockService = new StockManagementService();

echo "1. CHECKING ALL PRINT SERVICE PRODUCTS...\n";
$printProducts = Product::where('is_print_service', true)->with('activeVariants')->get();

echo "Found " . $printProducts->count() . " print service products:\n\n";

foreach ($printProducts as $product) {
    echo "Product ID: {$product->id}\n";
    echo "Name: {$product->name}\n";
    echo "SKU: {$product->sku}\n";
    echo "Status: {$product->status}\n";
    echo "Active Variants: " . $product->activeVariants->count() . "\n";
    
    if ($product->activeVariants->count() > 0) {
        echo "Variants:\n";
        foreach ($product->activeVariants as $variant) {
            echo "  - ID {$variant->id}: {$variant->paper_size} {$variant->print_type} = {$variant->stock} stock\n";
        }
    }
    echo "\n";
}

echo "2. CHECKING ADMIN STOCK MANAGEMENT DATA...\n";
$adminVariants = $stockService->getVariantsByStock('asc');

echo "Admin stock management shows " . $adminVariants->count() . " variants:\n\n";

$groupedVariants = [];
foreach ($adminVariants as $variant) {
    $key = $variant->paper_size . '_' . $variant->print_type;
    if (!isset($groupedVariants[$key])) {
        $groupedVariants[$key] = [];
    }
    $groupedVariants[$key][] = $variant;
}

foreach ($groupedVariants as $key => $variants) {
    echo "Combination: {$key}\n";
    
    if (count($variants) > 1) {
        echo "  ❌ DUPLICATE FOUND - " . count($variants) . " variants with same combination:\n";
        foreach ($variants as $variant) {
            echo "    - Product ID {$variant->product_id}, Variant ID {$variant->id}: {$variant->stock} stock\n";
        }
        echo "\n";
    } else {
        echo "  ✅ Single variant: Product ID {$variants[0]->product_id}, Variant ID {$variants[0]->id}: {$variants[0]->stock} stock\n\n";
    }
}

echo "3. IDENTIFYING DUPLICATE VARIANTS TO REMOVE...\n";

foreach ($groupedVariants as $key => $variants) {
    if (count($variants) > 1) {
        echo "Processing duplicates for {$key}:\n";
        
        $maxStock = 0;
        $keepVariant = null;
        $removeVariants = [];
        
        foreach ($variants as $variant) {
            if ($variant->stock > $maxStock) {
                if ($keepVariant) {
                    $removeVariants[] = $keepVariant;
                }
                $maxStock = $variant->stock;
                $keepVariant = $variant;
            } else {
                $removeVariants[] = $variant;
            }
        }
        
        echo "  ✅ KEEP: Variant ID {$keepVariant->id} (Product {$keepVariant->product_id}) - Stock: {$keepVariant->stock}\n";
        
        foreach ($removeVariants as $variant) {
            echo "  ❌ REMOVE: Variant ID {$variant->id} (Product {$variant->product_id}) - Stock: {$variant->stock}\n";
        }
        echo "\n";
    }
}

echo "4. CHECKING ASSOCIATED ORDERS...\n";

$allVariantIds = $adminVariants->pluck('id')->toArray();
$ordersCount = DB::table('print_orders')
    ->whereIn('paper_variant_id', $allVariantIds)
    ->count();

echo "Total print orders using these variants: {$ordersCount}\n\n";

foreach ($groupedVariants as $key => $variants) {
    if (count($variants) > 1) {
        echo "Orders for {$key} variants:\n";
        
        foreach ($variants as $variant) {
            $orderCount = DB::table('print_orders')
                ->where('paper_variant_id', $variant->id)
                ->count();
            echo "  - Variant ID {$variant->id}: {$orderCount} orders\n";
        }
        echo "\n";
    }
}

echo "5. RECOMMENDATION...\n";
echo "Found duplicate combinations that need to be resolved:\n";

foreach ($groupedVariants as $key => $variants) {
    if (count($variants) > 1) {
        $maxStock = 0;
        $keepVariant = null;
        $removeVariants = [];
        
        foreach ($variants as $variant) {
            if ($variant->stock > $maxStock) {
                if ($keepVariant) {
                    $removeVariants[] = $keepVariant;
                }
                $maxStock = $variant->stock;
                $keepVariant = $variant;
            } else {
                $removeVariants[] = $variant;
            }
        }
        
        echo "For {$key}:\n";
        echo "  - Keep Variant ID {$keepVariant->id} with {$keepVariant->stock} stock\n";
        
        foreach ($removeVariants as $variant) {
            $hasOrders = DB::table('print_orders')->where('paper_variant_id', $variant->id)->exists();
            if ($hasOrders) {
                echo "  - Variant ID {$variant->id} has orders - should be deactivated instead of deleted\n";
            } else {
                echo "  - Variant ID {$variant->id} can be safely deleted\n";
            }
        }
        echo "\n";
    }
}
