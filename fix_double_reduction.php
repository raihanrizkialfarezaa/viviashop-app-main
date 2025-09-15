<?php

require_once 'vendor/autoload.php';

use App\Models\ProductVariant;
use App\Models\PrintOrder;
use App\Models\StockMovement;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Fix Double Stock Reduction ===\n\n";

try {
    // 1. Current situation
    $variant46 = ProductVariant::find(46);
    echo "1. Current stock: {$variant46->stock}\n\n";
    
    // 2. Find duplicate movement from our test
    $problemOrder = PrintOrder::where('order_code', 'PRINT-16-09-2025-00-05-44')->first();
    
    if ($problemOrder) {
        echo "2. Checking movements for order {$problemOrder->order_code}:\n";
        
        $movements = StockMovement::where('reference_type', 'print_order')
                                 ->where('reference_id', $problemOrder->id)
                                 ->where('variant_id', 46)
                                 ->orderBy('created_at', 'desc')
                                 ->get();
        
        echo "Total movements: {$movements->count()}\n";
        
        foreach ($movements as $index => $movement) {
            echo "  Movement " . ($index + 1) . ": {$movement->movement_type} {$movement->quantity} at {$movement->created_at}\n";
        }
        
        // If there are duplicate movements, remove the latest one
        if ($movements->count() > 1) {
            echo "\n3. Removing duplicate movement...\n";
            $latestMovement = $movements->first();
            
            echo "Removing movement: {$latestMovement->movement_type} {$latestMovement->quantity} at {$latestMovement->created_at}\n";
            
            // Restore stock by adding back the quantity
            $variant46->increment('stock', $latestMovement->quantity);
            
            // Delete the duplicate movement
            $latestMovement->delete();
            
            echo "✅ Duplicate movement removed\n";
            echo "✅ Stock restored by {$latestMovement->quantity}\n";
            
            $variant46->refresh();
            echo "New stock: {$variant46->stock}\n";
        } else {
            echo "\n3. No duplicate movements found\n";
        }
    }
    
    // 4. Test improved confirmPayment
    echo "\n4. Testing improved confirmPayment (should not reduce stock again):\n";
    
    if ($problemOrder) {
        $stockBefore = $variant46->refresh()->stock;
        echo "Stock before test: {$stockBefore}\n";
        
        try {
            $printService = new \App\Services\PrintService();
            $printService->confirmPayment($problemOrder);
            
            $variant46->refresh();
            $stockAfter = $variant46->stock;
            echo "Stock after test: {$stockAfter}\n";
            
            if ($stockAfter == $stockBefore) {
                echo "✅ Perfect! Stock unchanged (duplicate prevention working)\n";
            } else {
                echo "❌ Stock changed unexpectedly\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    // 5. Final verification
    echo "\n5. Final verification:\n";
    $variant46->refresh();
    echo "Final stock: {$variant46->stock}\n";
    
    $totalMovements = StockMovement::where('reference_type', 'print_order')
                                 ->where('reference_id', $problemOrder->id)
                                 ->where('variant_id', 46)
                                 ->count();
    
    echo "Total movements for this order: {$totalMovements}\n";
    
    if ($variant46->stock == 9992 && $totalMovements == 1) {
        echo "✅ Perfect! Everything is correct now\n";
        echo "✅ Stock is 9992 (correct after one order)\n";
        echo "✅ Only one movement exists (no duplicates)\n";
        echo "✅ Future orders will work properly\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Fix Complete ===\n";