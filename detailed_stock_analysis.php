<?php

require_once 'vendor/autoload.php';

use App\Models\ProductVariant;
use App\Models\StockMovement;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Detailed Stock Analysis ===\n\n";

try {
    // Check specific variant ID 46 (A4 Berwarna - Rp 2)
    echo "1. Checking variant ID 46 (A4 Berwarna - Rp 2)...\n";
    
    $variant46 = ProductVariant::find(46);
    if ($variant46) {
        echo "✓ Found variant 46:\n";
        echo "  Name: {$variant46->name}\n";
        echo "  Price: Rp " . number_format($variant46->price, 2) . "\n";
        echo "  Stock Quantity: {$variant46->stock_quantity}\n";
        
        // Check all stock movements for this variant
        echo "\n2. Checking stock movements for variant 46...\n";
        $movements = StockMovement::where('variant_id', 46)
                                 ->orderBy('created_at', 'desc')
                                 ->get();
        
        if ($movements->count() > 0) {
            echo "Found " . $movements->count() . " stock movements:\n";
            foreach ($movements as $movement) {
                echo "  - {$movement->created_at} | {$movement->type} | Qty: {$movement->quantity} | Before: {$movement->quantity_before} | After: {$movement->quantity_after}\n";
                echo "    Ref: {$movement->reference_type}:{$movement->reference_id} | Notes: {$movement->notes}\n";
            }
        } else {
            echo "❌ NO STOCK MOVEMENTS found for variant 46!\n";
            echo "This explains why stock doesn't decrease.\n";
        }
        
        // Check recent paid orders to see if they should have created stock movements
        echo "\n3. Checking recent paid print orders...\n";
        
        $recentOrders = \App\Models\PrintOrder::where('payment_status', 'paid')
                                             ->where('created_at', '>=', now()->subDays(1))
                                             ->orderBy('created_at', 'desc')
                                             ->get();
        
        echo "Recent paid orders (last 24h):\n";
        foreach ($recentOrders as $order) {
            echo "  - {$order->order_code} | {$order->status} | Total: Rp {$order->total_amount}\n";
            
            // Check if this order should have affected stock
            $orderData = json_decode($order->order_data, true);
            if ($orderData && isset($orderData['product_variants'])) {
                foreach ($orderData['product_variants'] as $variantData) {
                    if ($variantData['variant_id'] == 46) {
                        echo "    → Should have reduced stock by {$variantData['quantity']} units\n";
                        
                        // Check if stock movement exists for this order
                        $orderMovement = StockMovement::where('reference_type', 'App\\Models\\PrintOrder')
                                                    ->where('reference_id', $order->id)
                                                    ->where('variant_id', 46)
                                                    ->first();
                        
                        if ($orderMovement) {
                            echo "    ✓ Stock movement exists\n";
                        } else {
                            echo "    ❌ NO STOCK MOVEMENT for this order!\n";
                        }
                    }
                }
            }
        }
        
    } else {
        echo "❌ Variant 46 not found\n";
    }
    
    // Check if there's a stock service or movement logic issue
    echo "\n4. Checking PrintService stock logic...\n";
    
    // Check if StockService exists and is being called
    if (class_exists('App\\Services\\StockService')) {
        echo "✓ StockService class exists\n";
    } else {
        echo "❌ StockService class missing\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Analysis Complete ===\n";