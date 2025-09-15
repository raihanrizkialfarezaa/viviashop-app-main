<?php

require_once 'vendor/autoload.php';

use App\Models\ProductVariant;
use App\Models\PrintOrder;
use App\Models\StockMovement;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Investigate Current Stock and Page Count Issues ===\n\n";

try {
    // 1. Check current stock for variant 46
    echo "1. Current stock status:\n";
    $variant46 = ProductVariant::find(46);
    echo "Variant 46 stock: {$variant46->stock_quantity}\n\n";
    
    // 2. Check recent orders
    echo "2. Recent paid orders (last 2 hours):\n";
    $recentOrders = PrintOrder::where('payment_status', 'paid')
                             ->where('updated_at', '>=', now()->subHours(2))
                             ->orderBy('updated_at', 'desc')
                             ->get();
    
    foreach ($recentOrders as $order) {
        echo "Order: {$order->order_code}\n";
        echo "  Customer: {$order->customer_name}\n";
        echo "  Status: {$order->status}\n";
        echo "  Payment Status: {$order->payment_status}\n";
        echo "  Total: Rp {$order->total_amount}\n";
        echo "  Updated: {$order->updated_at}\n";
        
        // Check order data
        $orderData = json_decode($order->order_data, true);
        if ($orderData && isset($orderData['product_variants'])) {
            echo "  Variants ordered:\n";
            foreach ($orderData['product_variants'] as $variant) {
                echo "    - Variant {$variant['variant_id']}: {$variant['quantity']} units @ Rp{$variant['price']}\n";
                
                if ($variant['variant_id'] == 46) {
                    echo "      🎯 This order used A4 Color variant!\n";
                    
                    // Check if stock movement exists for this order
                    $stockMovement = StockMovement::where('reference_type', 'print_order')
                                                 ->where('reference_id', $order->id)
                                                 ->where('variant_id', 46)
                                                 ->first();
                    
                    if ($stockMovement) {
                        echo "      ✅ Stock movement exists: {$stockMovement->movement_type} {$stockMovement->quantity}\n";
                    } else {
                        echo "      ❌ NO STOCK MOVEMENT FOUND for this order!\n";
                    }
                }
            }
        }
        
        // Check files and page count
        if ($order->files()->count() > 0) {
            echo "  Files:\n";
            foreach ($order->files as $file) {
                echo "    - {$file->file_name}: {$file->pages_count} pages\n";
            }
        }
        echo "\n";
    }
    
    // 3. Check all stock movements for variant 46
    echo "3. All stock movements for variant 46:\n";
    $allMovements = StockMovement::where('variant_id', 46)
                                ->orderBy('created_at', 'desc')
                                ->get();
    
    foreach ($allMovements as $movement) {
        echo "  - {$movement->created_at}: {$movement->movement_type} {$movement->quantity} ";
        echo "(Stock: {$movement->old_stock} → {$movement->new_stock}) ";
        echo "Ref: {$movement->reference_type}:{$movement->reference_id}\n";
    }
    
    // 4. Check if recent orders should have created stock movements
    echo "\n4. Analysis:\n";
    
    $expectedReductions = 0;
    $actualReductions = 0;
    
    foreach ($recentOrders as $order) {
        $orderData = json_decode($order->order_data, true);
        if ($orderData && isset($orderData['product_variants'])) {
            foreach ($orderData['product_variants'] as $variant) {
                if ($variant['variant_id'] == 46) {
                    $expectedReductions += $variant['quantity'];
                    
                    $stockMovement = StockMovement::where('reference_type', 'print_order')
                                                 ->where('reference_id', $order->id)
                                                 ->where('variant_id', 46)
                                                 ->first();
                    if ($stockMovement) {
                        $actualReductions += $stockMovement->quantity;
                    }
                }
            }
        }
    }
    
    echo "Expected stock reductions: {$expectedReductions}\n";
    echo "Actual stock reductions: {$actualReductions}\n";
    echo "Missing reductions: " . ($expectedReductions - $actualReductions) . "\n\n";
    
    if ($expectedReductions > $actualReductions) {
        echo "❌ PROBLEM: Some recent orders did not create stock movements!\n";
        echo "This explains why stock is stuck at 9998.\n";
        
        // Look for orders without stock movements
        echo "\nOrders missing stock movements:\n";
        foreach ($recentOrders as $order) {
            $orderData = json_decode($order->order_data, true);
            if ($orderData && isset($orderData['product_variants'])) {
                foreach ($orderData['product_variants'] as $variant) {
                    if ($variant['variant_id'] == 46) {
                        $stockMovement = StockMovement::where('reference_type', 'print_order')
                                                     ->where('reference_id', $order->id)
                                                     ->where('variant_id', 46)
                                                     ->first();
                        if (!$stockMovement) {
                            echo "  - Order {$order->order_code} (ID: {$order->id})\n";
                        }
                    }
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Investigation Complete ===\n";