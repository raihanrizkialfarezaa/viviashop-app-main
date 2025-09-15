<?php

require_once 'vendor/autoload.php';

use App\Models\ProductVariant;
use App\Models\PrintOrder;
use App\Models\StockMovement;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Fix Latest Order Stock Issue ===\n\n";

try {
    // 1. Check current stock
    echo "1. Current stock status:\n";
    $variant46 = ProductVariant::find(46);
    echo "Variant 46 stock: {$variant46->stock}\n\n";
    
    // 2. Find order that should have reduced stock
    echo "2. Finding problematic order:\n";
    $problemOrder = PrintOrder::where('order_code', 'PRINT-16-09-2025-00-05-44')->first();
    
    if ($problemOrder) {
        echo "Order found: {$problemOrder->order_code}\n";
        echo "  Status: {$problemOrder->status}\n";
        echo "  Payment Status: {$problemOrder->payment_status}\n";
        echo "  Paper Variant ID: {$problemOrder->paper_variant_id}\n";
        echo "  Total Pages: {$problemOrder->total_pages}\n";
        echo "  Quantity: {$problemOrder->quantity}\n";
        echo "  Created: {$problemOrder->created_at}\n";
        echo "  Updated: {$problemOrder->updated_at}\n\n";
        
        // Check if stock movement exists
        $movement = StockMovement::where('reference_type', 'print_order')
                                ->where('reference_id', $problemOrder->id)
                                ->where('variant_id', 46)
                                ->first();
        
        if ($movement) {
            echo "✅ Stock movement already exists\n";
        } else {
            echo "❌ NO STOCK MOVEMENT - Creating it now...\n";
            
            // Calculate stock reduction needed
            $reductionNeeded = $problemOrder->total_pages * $problemOrder->quantity;
            echo "Stock reduction needed: {$reductionNeeded}\n";
            
            try {
                // Create stock movement manually
                StockMovement::create([
                    'variant_id' => 46,
                    'movement_type' => 'out',
                    'quantity' => $reductionNeeded,
                    'reference_type' => 'print_order',
                    'reference_id' => $problemOrder->id,
                    'description' => 'Stock reduction for completed order (manual fix)',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                // Reduce actual stock
                $variant46->decrement('stock', $reductionNeeded);
                
                echo "✅ Stock movement created successfully\n";
                echo "✅ Stock reduced by {$reductionNeeded}\n";
                
                $variant46->refresh();
                echo "New stock: {$variant46->stock}\n\n";
                
            } catch (Exception $e) {
                echo "❌ Failed to create stock movement: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "Order not found\n";
    }
    
    // 3. Check what should trigger confirmPayment
    echo "3. Debugging payment confirmation flow...\n";
    
    // Check PrintServiceController methods
    $controllerPath = 'app/Http/Controllers/PrintServiceController.php';
    if (file_exists($controllerPath)) {
        echo "Checking PrintServiceController methods:\n";
        
        $content = file_get_contents($controllerPath);
        if (strpos($content, 'confirmPayment') !== false) {
            echo "✅ confirmPayment is called in PrintServiceController\n";
        } else {
            echo "❌ confirmPayment NOT found in PrintServiceController\n";
        }
        
        if (strpos($content, 'paymentFinish') !== false) {
            echo "✅ paymentFinish method exists\n";
        }
        
        if (strpos($content, 'StockService') !== false) {
            echo "✅ StockService is used in controller\n";
        } else {
            echo "❌ StockService NOT imported/used\n";
        }
    }
    
    // 4. Check if the issue is in confirmPayment method itself
    echo "\n4. Testing confirmPayment method directly:\n";
    
    if ($problemOrder && $problemOrder->payment_status === 'paid') {
        echo "Calling confirmPayment for order {$problemOrder->order_code}...\n";
        
        try {
            $printService = new \App\Services\PrintService();
            
            // Use reflection to call private/protected method if needed
            $reflection = new ReflectionClass($printService);
            if ($reflection->hasMethod('confirmPayment')) {
                $method = $reflection->getMethod('confirmPayment');
                $method->setAccessible(true);
                $result = $method->invoke($printService, $problemOrder->id);
                echo "✅ confirmPayment executed successfully\n";
            } else {
                echo "❌ confirmPayment method not found in PrintService\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error calling confirmPayment: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n5. Final stock check:\n";
    $variant46->refresh();
    echo "Final stock: {$variant46->stock}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Fix Complete ===\n";