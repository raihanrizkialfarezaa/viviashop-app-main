<?php

require_once 'vendor/autoload.php';

use App\Models\ProductVariant;
use App\Models\PrintOrder;
use App\Models\StockMovement;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Fix Missing Stock Movement for Order 64 ===\n\n";

try {
    // 1. Get the problematic order
    $order64 = PrintOrder::find(64);
    
    if ($order64) {
        echo "1. Order found: {$order64->order_code}\n";
        echo "   Status: {$order64->status} | Payment: {$order64->payment_status}\n";
        echo "   Pages: {$order64->total_pages} x Qty: {$order64->quantity}\n";
        
        // Check if stock movement already exists
        $existingMovement = StockMovement::where('reference_type', 'print_order')
                                        ->where('reference_id', 64)
                                        ->where('variant_id', 46)
                                        ->first();
        
        if ($existingMovement) {
            echo "   ✅ Stock movement already exists\n";
        } else {
            echo "   ❌ NO STOCK MOVEMENT - Creating now...\n";
            
            $variant46 = ProductVariant::find(46);
            $currentStock = $variant46->stock;
            $reductionNeeded = $order64->total_pages * $order64->quantity;
            $newStock = $currentStock - $reductionNeeded;
            
            echo "   Current stock: {$currentStock}\n";
            echo "   Reduction needed: {$reductionNeeded}\n";
            echo "   New stock: {$newStock}\n";
            
            // Create the missing stock movement
            StockMovement::create([
                'variant_id' => 46,
                'movement_type' => 'out',
                'quantity' => $reductionNeeded,
                'old_stock' => $currentStock,
                'new_stock' => $newStock,
                'reference_type' => 'print_order',
                'reference_id' => 64,
                'reason' => 'order_confirmed',
                'notes' => 'Missing stock movement for paid order (auto-fix)',
                'created_at' => $order64->updated_at, // Use order update time
                'updated_at' => now()
            ]);
            
            // Update the actual stock
            $variant46->update(['stock' => $newStock]);
            
            echo "   ✅ Stock movement created\n";
            echo "   ✅ Stock updated from {$currentStock} to {$newStock}\n";
        }
    } else {
        echo "❌ Order 64 not found\n";
    }
    
    // 2. Verify the fix
    echo "\n2. Verification:\n";
    $variant46 = ProductVariant::find(46);
    echo "Current stock: {$variant46->stock}\n";
    
    // Check movements for order 64
    $movements = StockMovement::where('reference_type', 'print_order')
                             ->where('reference_id', 64)
                             ->where('variant_id', 46)
                             ->get();
    
    echo "Stock movements for order 64: {$movements->count()}\n";
    foreach ($movements as $mov) {
        echo "  - {$mov->movement_type} {$mov->quantity} at {$mov->created_at}\n";
    }
    
    // 3. Check if there are any other paid orders without stock movements
    echo "\n3. Checking for other problematic orders:\n";
    $paidOrdersWithoutMovements = PrintOrder::where('paper_variant_id', 46)
                                           ->where('payment_status', 'paid')
                                           ->whereNotIn('id', function($query) {
                                               $query->select('reference_id')
                                                     ->from('stock_movements')
                                                     ->where('reference_type', 'print_order')
                                                     ->where('variant_id', 46)
                                                     ->where('movement_type', 'out');
                                           })
                                           ->get();
    
    if ($paidOrdersWithoutMovements->count() > 0) {
        echo "Found {$paidOrdersWithoutMovements->count()} more paid orders without stock movements:\n";
        foreach ($paidOrdersWithoutMovements as $order) {
            echo "  - Order {$order->id}: {$order->order_code} | {$order->created_at}\n";
        }
    } else {
        echo "✅ No other paid orders without stock movements\n";
    }
    
    echo "\n4. Expected vs Actual Stock:\n";
    
    // Calculate what stock should be based on all movements
    $baseStock = 9998;
    $totalOut = StockMovement::where('variant_id', 46)
                            ->where('movement_type', 'out')
                            ->where('reason', 'order_confirmed')
                            ->sum('quantity');
    
    $totalIn = StockMovement::where('variant_id', 46)
                           ->where('movement_type', 'in')
                           ->sum('quantity');
    
    $expectedStock = $baseStock - $totalOut + $totalIn;
    
    echo "Base stock: {$baseStock}\n";
    echo "Total out: {$totalOut}\n";
    echo "Total in: {$totalIn}\n";
    echo "Expected stock: {$expectedStock}\n";
    echo "Actual stock: {$variant46->stock}\n";
    
    if ($variant46->stock == $expectedStock) {
        echo "✅ Stock is now correct!\n";
    } else {
        echo "❌ Stock still has discrepancy\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Fix Complete ===\n";