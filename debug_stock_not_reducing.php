<?php

require_once 'vendor/autoload.php';

use App\Models\ProductVariant;
use App\Models\PrintOrder;
use App\Models\StockMovement;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEBUG: Why Stock Still Not Reducing Automatically ===\n\n";

try {
    // 1. Current stock status
    $variant46 = ProductVariant::find(46);
    echo "1. Current stock: {$variant46->stock}\n\n";
    
    // 2. Find latest orders (last 30 minutes)
    echo "2. Latest orders (last 30 minutes):\n";
    $latestOrders = PrintOrder::where('paper_variant_id', 46)
                             ->where('created_at', '>=', now()->subMinutes(30))
                             ->orderBy('created_at', 'desc')
                             ->get();
    
    $totalExpectedReduction = 0;
    $ordersWithoutMovements = [];
    
    foreach ($latestOrders as $order) {
        $reduction = $order->total_pages * $order->quantity;
        
        echo "Order: {$order->order_code} (ID: {$order->id})\n";
        echo "  Created: {$order->created_at}\n";
        echo "  Updated: {$order->updated_at}\n";
        echo "  Status: {$order->status}\n";
        echo "  Payment Status: {$order->payment_status}\n";
        echo "  Payment Method: {$order->payment_method}\n";
        echo "  Pages: {$order->total_pages} x Qty: {$order->quantity} = {$reduction} reduction\n";
        
        if ($order->payment_status === 'paid' || $order->status === 'completed') {
            $totalExpectedReduction += $reduction;
        }
        
        // Check stock movement
        $movement = StockMovement::where('reference_type', 'print_order')
                                ->where('reference_id', $order->id)
                                ->where('variant_id', 46)
                                ->first();
        
        if ($movement) {
            echo "  ✅ Stock movement: {$movement->movement_type} {$movement->quantity} at {$movement->created_at}\n";
        } else {
            echo "  ❌ NO STOCK MOVEMENT!\n";
            if ($order->payment_status === 'paid' || $order->status === 'completed') {
                $ordersWithoutMovements[] = $order;
                echo "     🚨 CRITICAL: Paid/Completed order without stock movement!\n";
            }
        }
        echo "\n";
    }
    
    echo "Total expected reduction from recent orders: {$totalExpectedReduction}\n";
    echo "Orders without movements: " . count($ordersWithoutMovements) . "\n\n";
    
    // 3. Check what's happening in payment confirmation
    if (count($ordersWithoutMovements) > 0) {
        echo "3. Analyzing problematic orders:\n";
        
        foreach ($ordersWithoutMovements as $order) {
            echo "Problematic Order: {$order->order_code}\n";
            echo "  Payment Method: {$order->payment_method}\n";
            echo "  Payment Status: {$order->payment_status}\n";
            echo "  Status: {$order->status}\n";
            
            // Check if this is manual or online payment
            if ($order->payment_method === 'manual') {
                echo "  💡 Manual payment - should be confirmed by admin or processPayment\n";
            } else {
                echo "  💡 Online payment - should be confirmed by paymentFinish callback\n";
            }
            
            // Try to confirm payment manually
            echo "  🔧 Attempting manual confirmPayment...\n";
            
            try {
                $printService = new \App\Services\PrintService();
                $printService->confirmPayment($order);
                echo "  ✅ Manual confirmPayment successful!\n";
                
                // Check if stock was reduced
                $variant46->refresh();
                echo "  📊 Stock after manual confirm: {$variant46->stock}\n";
                
            } catch (Exception $e) {
                echo "  ❌ Manual confirmPayment failed: " . $e->getMessage() . "\n";
            }
            echo "\n";
        }
    }
    
    // 4. Check payment confirmation flow
    echo "4. Checking payment confirmation flow:\n";
    
    // Check PrintServiceController paymentFinish method
    $controllerPath = 'app/Http/Controllers/PrintServiceController.php';
    if (file_exists($controllerPath)) {
        $content = file_get_contents($controllerPath);
        
        if (strpos($content, 'confirmPayment($printOrder)') !== false) {
            echo "✅ paymentFinish calls confirmPayment() - OK\n";
        } else {
            echo "❌ paymentFinish does NOT call confirmPayment() - PROBLEM!\n";
        }
        
        if (strpos($content, 'StockService') !== false) {
            echo "✅ StockService imported - OK\n";
        } else {
            echo "❌ StockService NOT imported - PROBLEM!\n";
        }
    }
    
    // 5. Check if there are any recent successful payments
    echo "\n5. Recent payment activities:\n";
    
    $recentPaidOrders = PrintOrder::where('paper_variant_id', 46)
                                 ->where('payment_status', 'paid')
                                 ->where('updated_at', '>=', now()->subHour())
                                 ->orderBy('updated_at', 'desc')
                                 ->get();
    
    echo "Recent paid orders (last hour): {$recentPaidOrders->count()}\n";
    
    foreach ($recentPaidOrders as $order) {
        echo "  - {$order->order_code} | Updated: {$order->updated_at} | Method: {$order->payment_method}\n";
    }
    
    // 6. Final stock verification
    echo "\n6. Final verification:\n";
    $variant46->refresh();
    echo "Final stock: {$variant46->stock}\n";
    
    $recentMovements = StockMovement::where('variant_id', 46)
                                   ->where('created_at', '>=', now()->subHour())
                                   ->orderBy('created_at', 'desc')
                                   ->get();
    
    echo "Recent stock movements (last hour): {$recentMovements->count()}\n";
    foreach ($recentMovements as $mov) {
        echo "  - {$mov->movement_type} {$mov->quantity} at {$mov->created_at} (Order #{$mov->reference_id})\n";
    }
    
    if (count($ordersWithoutMovements) > 0) {
        echo "\n🚨 ROOT CAUSE: There are completed/paid orders without stock movements!\n";
        echo "The confirmPayment() flow is not being triggered properly.\n";
    } else {
        echo "\n✅ All recent orders have proper stock movements.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug Complete ===\n";