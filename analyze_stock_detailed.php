<?php

require_once 'vendor/autoload.php';

use App\Models\ProductVariant;
use App\Models\PrintOrder;
use App\Models\StockMovement;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Check Current Stock Status - Detailed Analysis ===\n\n";

try {
    // 1. Current stock
    $variant46 = ProductVariant::find(46);
    echo "1. Current Stock: {$variant46->stock}\n\n";
    
    // 2. Check all recent orders
    echo "2. Recent orders (last 2 hours):\n";
    $recentOrders = PrintOrder::where('paper_variant_id', 46)
                             ->where('created_at', '>=', now()->subHours(2))
                             ->orderBy('created_at', 'desc')
                             ->get();
    
    $totalExpectedReduction = 0;
    foreach ($recentOrders as $order) {
        $orderReduction = $order->total_pages * $order->quantity;
        $totalExpectedReduction += $orderReduction;
        
        echo "Order: {$order->order_code}\n";
        echo "  Created: {$order->created_at}\n";
        echo "  Status: {$order->status} | Payment: {$order->payment_status}\n";
        echo "  Pages: {$order->total_pages} x Qty: {$order->quantity} = {$orderReduction} reduction\n";
        
        // Check stock movement
        $movement = StockMovement::where('reference_type', 'print_order')
                                ->where('reference_id', $order->id)
                                ->where('variant_id', 46)
                                ->first();
        
        if ($movement) {
            echo "  ✅ Stock movement: {$movement->movement_type} {$movement->quantity} at {$movement->created_at}\n";
        } else {
            echo "  ❌ NO STOCK MOVEMENT for {$order->payment_status} order\n";
            
            if ($order->payment_status === 'paid') {
                echo "     🚨 PROBLEM: Paid order without stock movement!\n";
            }
        }
        echo "\n";
    }
    
    echo "Total expected reduction from recent orders: {$totalExpectedReduction}\n\n";
    
    // 3. Check all stock movements for variant 46
    echo "3. All stock movements for variant 46 (last 2 hours):\n";
    $movements = StockMovement::where('variant_id', 46)
                             ->where('created_at', '>=', now()->subHours(2))
                             ->orderBy('created_at', 'desc')
                             ->get();
    
    $totalActualReduction = 0;
    foreach ($movements as $movement) {
        if ($movement->movement_type === 'out') {
            $totalActualReduction += $movement->quantity;
        }
        
        echo "Movement: {$movement->movement_type} {$movement->quantity}\n";
        echo "  From: {$movement->old_stock} → To: {$movement->new_stock}\n";
        echo "  Reference: {$movement->reference_type} #{$movement->reference_id}\n";
        echo "  Reason: {$movement->reason}\n";
        echo "  Created: {$movement->created_at}\n\n";
    }
    
    echo "Total actual reduction from movements: {$totalActualReduction}\n\n";
    
    // 4. Calculate what stock should be
    $baseStock = 9998; // Original stock before any orders
    $expectedCurrentStock = $baseStock - $totalActualReduction;
    
    echo "4. Stock calculation:\n";
    echo "Base stock: {$baseStock}\n";
    echo "Total reductions: {$totalActualReduction}\n";
    echo "Expected stock: {$expectedCurrentStock}\n";
    echo "Actual stock: {$variant46->stock}\n";
    
    if ($variant46->stock != $expectedCurrentStock) {
        echo "❌ Stock mismatch detected!\n";
        $discrepancy = $expectedCurrentStock - $variant46->stock;
        echo "Discrepancy: {$discrepancy}\n";
    } else {
        echo "✅ Stock is correct\n";
    }
    
    // 5. Find orders that should have reduced stock but didn't
    echo "\n5. Finding paid orders without stock movements:\n";
    $problematicOrders = PrintOrder::where('paper_variant_id', 46)
                                  ->where('payment_status', 'paid')
                                  ->whereDoesntHave('stockMovement', function($q) {
                                      $q->where('variant_id', 46)->where('movement_type', 'out');
                                  })
                                  ->get();
    
    if ($problematicOrders->count() > 0) {
        echo "Found {$problematicOrders->count()} paid orders without stock movements:\n";
        foreach ($problematicOrders as $order) {
            $reduction = $order->total_pages * $order->quantity;
            echo "  - {$order->order_code} | {$order->created_at} | Should reduce: {$reduction}\n";
        }
    } else {
        echo "✅ All paid orders have stock movements\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Analysis Complete ===\n";