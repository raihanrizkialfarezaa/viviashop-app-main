<?php

require_once 'vendor/autoload.php';

use App\Models\ProductVariant;
use App\Models\PrintOrder;
use App\Models\StockMovement;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Check Current Stock Status vs Recent Orders ===\n\n";

try {
    // 1. Check variant 46 current stock
    echo "1. Current variant 46 stock:\n";
    $variant46 = ProductVariant::find(46);
    echo "Database stock value: {$variant46->stock}\n\n";
    
    // 2. Check very recent orders (last 1 hour)
    echo "2. Very recent orders (last 1 hour):\n";
    $recentOrders = PrintOrder::where('payment_status', 'paid')
                             ->where('updated_at', '>=', now()->subHour())
                             ->orderBy('updated_at', 'desc')
                             ->get();
    
    $shouldHaveReduced = 0;
    
    if ($recentOrders->count() > 0) {
        foreach ($recentOrders as $order) {
            echo "Order: {$order->order_code}\n";
            echo "  Status: {$order->status}\n";
            echo "  Payment Status: {$order->payment_status}\n";
            echo "  Updated: {$order->updated_at}\n";
            echo "  Total Pages: {$order->total_pages}\n";
            echo "  Quantity: {$order->quantity}\n";
            echo "  Paper Variant ID: {$order->paper_variant_id}\n";
            
            if ($order->paper_variant_id == 46) {
                $expectedReduction = $order->total_pages * $order->quantity;
                $shouldHaveReduced += $expectedReduction;
                echo "  → This order should reduce stock by: {$expectedReduction}\n";
                
                // Check if stock movement exists
                $movement = StockMovement::where('reference_type', 'print_order')
                                       ->where('reference_id', $order->id)
                                       ->where('variant_id', 46)
                                       ->first();
                
                if ($movement) {
                    echo "  ✅ Stock movement exists: {$movement->movement_type} {$movement->quantity}\n";
                } else {
                    echo "  ❌ NO STOCK MOVEMENT for this order!\n";
                }
            }
            echo "\n";
        }
    } else {
        echo "No recent orders found in last hour\n\n";
    }
    
    // 3. Check all stock movements for variant 46 today
    echo "3. All stock movements for variant 46 today:\n";
    $todayMovements = StockMovement::where('variant_id', 46)
                                  ->whereDate('created_at', today())
                                  ->orderBy('created_at', 'desc')
                                  ->get();
    
    $totalReductionsToday = 0;
    foreach ($todayMovements as $movement) {
        echo "  - {$movement->created_at}: {$movement->movement_type} {$movement->quantity}\n";
        echo "    Stock: {$movement->old_stock} → {$movement->new_stock}\n";
        echo "    Ref: {$movement->reference_type}:{$movement->reference_id}\n";
        
        if ($movement->movement_type == 'out') {
            $totalReductionsToday += $movement->quantity;
        }
    }
    
    echo "\nTotal stock reductions today: {$totalReductionsToday}\n";
    echo "Expected stock reductions from recent orders: {$shouldHaveReduced}\n";
    echo "Missing reductions: " . ($shouldHaveReduced - $totalReductionsToday) . "\n\n";
    
    // 4. Force check current stock calculation
    echo "4. Force recalculate stock:\n";
    
    $allMovements = StockMovement::where('variant_id', 46)->get();
    $totalOut = $allMovements->where('movement_type', 'out')->sum('quantity');
    $totalIn = $allMovements->where('movement_type', 'in')->sum('quantity');
    
    echo "Total OUT movements: {$totalOut}\n";
    echo "Total IN movements: {$totalIn}\n";
    
    $calculatedStock = 10000 - $totalOut + $totalIn;
    echo "Calculated stock should be: {$calculatedStock}\n";
    echo "Database stock is: {$variant46->stock}\n";
    
    if ($calculatedStock != $variant46->stock) {
        echo "❌ MISMATCH! Updating database stock...\n";
        $variant46->update(['stock' => $calculatedStock]);
        echo "✅ Stock updated to: {$calculatedStock}\n";
    } else {
        echo "✅ Stock is correct\n";
    }
    
    // 5. Test if recent orders need manual stock reduction
    if ($shouldHaveReduced > $totalReductionsToday) {
        echo "\n5. Fixing missing stock reductions...\n";
        
        foreach ($recentOrders as $order) {
            if ($order->paper_variant_id == 46) {
                $movement = StockMovement::where('reference_type', 'print_order')
                                       ->where('reference_id', $order->id)
                                       ->where('variant_id', 46)
                                       ->first();
                
                if (!$movement) {
                    echo "Creating missing stock movement for order {$order->order_code}...\n";
                    
                    $stockService = new \App\Services\StockService();
                    $reduction = $order->total_pages * $order->quantity;
                    
                    try {
                        $stockService->reduceStock(46, $reduction, $order->id, 'order_confirmed');
                        echo "✅ Stock reduced by {$reduction} for order {$order->order_code}\n";
                    } catch (Exception $e) {
                        echo "❌ Failed to reduce stock: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }
    
    // 6. Final stock check
    echo "\n6. Final stock status:\n";
    $variant46->refresh();
    echo "Final stock: {$variant46->stock}\n";
    
    if ($variant46->stock < 9998) {
        echo "✅ Stock has been reduced from 9998!\n";
    } else {
        echo "⚠️ Stock still at 9998 - may need to make a new order to test\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Check Complete ===\n";