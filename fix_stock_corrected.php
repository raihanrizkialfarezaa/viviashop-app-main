<?php

require_once 'vendor/autoload.php';

use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Fix Stock Issues - Corrected Version ===\n\n";

try {
    // Step 1: Fix variant 46 stock_quantity
    echo "1. Fixing stock_quantity for variant 46...\n";
    
    $variant46 = ProductVariant::find(46);
    if ($variant46) {
        echo "Current stock_quantity: " . ($variant46->stock_quantity ?? 'NULL') . "\n";
        
        // Calculate actual stock based on movements using correct column names
        $totalOut = StockMovement::where('variant_id', 46)
                                ->where('movement_type', 'out')
                                ->sum('quantity');
        
        $totalIn = StockMovement::where('variant_id', 46)
                               ->where('movement_type', 'in')
                               ->sum('quantity');
        
        echo "Total IN: {$totalIn}\n";
        echo "Total OUT: {$totalOut}\n";
        
        // Calculate correct stock (start with 10000, subtract OUT, add IN)
        $correctStock = 10000 - $totalOut + $totalIn;
        
        $variant46->update(['stock_quantity' => $correctStock]);
        echo "✓ Updated stock to correct value: {$correctStock}\n\n";
    }
    
    // Step 2: Check all stock movements for variant 46
    echo "2. Checking existing stock movements for variant 46...\n";
    
    $movements = StockMovement::where('variant_id', 46)
                             ->orderBy('created_at', 'desc')
                             ->get();
    
    echo "Found " . $movements->count() . " movements:\n";
    foreach ($movements as $movement) {
        echo "  - ID: {$movement->id} | Type: {$movement->movement_type} | Qty: {$movement->quantity} | Old: {$movement->old_stock} | New: {$movement->new_stock}\n";
        echo "    Reason: {$movement->reason} | Ref: {$movement->reference_type}:{$movement->reference_id}\n";
    }
    
    // Step 3: Fix all print service variants stock_quantity
    echo "\n3. Checking all print service variants stock...\n";
    
    $printVariants = ProductVariant::whereHas('product', function($q) {
        $q->where('is_print_service', true);
    })->get();
    
    foreach ($printVariants as $variant) {
        $currentStock = $variant->stock_quantity;
        echo "Variant {$variant->id} ({$variant->name}): ";
        
        if (is_null($currentStock)) {
            // Calculate correct stock based on movements
            $totalOut = StockMovement::where('variant_id', $variant->id)
                                    ->where('movement_type', 'out')
                                    ->sum('quantity');
            
            $totalIn = StockMovement::where('variant_id', $variant->id)
                                   ->where('movement_type', 'in')
                                   ->sum('quantity');
            
            $correctStock = 10000 - $totalOut + $totalIn;
            
            $variant->update(['stock_quantity' => $correctStock]);
            echo "Fixed stock from NULL to {$correctStock}\n";
        } else {
            echo "Stock OK: {$currentStock}\n";
        }
    }
    
    // Step 4: Test current status
    echo "\n4. Final verification - A4 Color variant status...\n";
    
    $variant46->refresh();
    echo "✅ Variant 46 (A4 Color) final stock: {$variant46->stock_quantity}\n";
    
    if ($variant46->stock_quantity < 10000) {
        echo "✅ Stock has been properly reduced from transactions!\n";
        echo "Difference from initial 10000: " . (10000 - $variant46->stock_quantity) . " units\n";
    } else {
        echo "⚠️  Stock still at 10000 - no reductions recorded\n";
    }
    
    // Step 5: Show recent transactions that should affect stock
    echo "\n5. Recent orders that should have reduced stock...\n";
    
    $recentOrders = \App\Models\PrintOrder::where('payment_status', 'paid')
                                         ->where('created_at', '>=', now()->subHours(24))
                                         ->get();
    
    $shouldHaveReduced = 0;
    foreach ($recentOrders as $order) {
        $orderData = json_decode($order->order_data, true);
        if ($orderData && isset($orderData['product_variants'])) {
            foreach ($orderData['product_variants'] as $variantData) {
                if ($variantData['variant_id'] == 46) {
                    $shouldHaveReduced += $variantData['quantity'];
                    echo "  - Order {$order->order_code}: should reduce by {$variantData['quantity']}\n";
                }
            }
        }
    }
    
    echo "\nTotal stock reduction expected: {$shouldHaveReduced} units\n";
    echo "Actual stock reduction: " . (10000 - $variant46->stock_quantity) . " units\n";
    
    if ($shouldHaveReduced > (10000 - $variant46->stock_quantity)) {
        echo "❌ Some transactions did not reduce stock properly\n";
    } else {
        echo "✅ Stock reductions match expected transactions\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Fix Complete ===\n";