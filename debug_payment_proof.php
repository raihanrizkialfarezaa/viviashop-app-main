<?php

require_once 'vendor/autoload.php';

use App\Models\PrintOrder;
use Illuminate\Support\Facades\DB;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Debugging Payment Proof 500 Error ===\n\n";

try {
    // 1. Check order 64
    echo "1. Checking order ID 64:\n";
    $order = PrintOrder::find(64);
    
    if ($order) {
        echo "✅ Order found: {$order->order_code}\n";
        echo "   Payment status: {$order->payment_status}\n";
        echo "   Payment method: {$order->payment_method}\n";
        echo "   Payment proof: " . ($order->payment_proof ?? 'NULL') . "\n";
        
        if ($order->payment_proof) {
            $fullPath = storage_path('app/' . $order->payment_proof);
            echo "   Full path: {$fullPath}\n";
            echo "   File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
            
            if (file_exists($fullPath)) {
                echo "   File size: " . filesize($fullPath) . " bytes\n";
            }
        } else {
            echo "   ❌ No payment proof stored for this order\n";
        }
    } else {
        echo "❌ Order ID 64 not found\n";
    }
    
    // 2. Check table structure
    echo "\n2. Checking print_orders table structure:\n";
    $columns = DB::select("DESCRIBE print_orders");
    
    $hasPaymentProof = false;
    foreach ($columns as $column) {
        if ($column->Field === 'payment_proof') {
            $hasPaymentProof = true;
            echo "✅ payment_proof field exists: {$column->Type}\n";
            break;
        }
    }
    
    if (!$hasPaymentProof) {
        echo "❌ payment_proof field does NOT exist in print_orders table\n";
    }
    
    // 3. Check recent orders with payment_proof
    echo "\n3. Checking recent orders with payment_proof:\n";
    $ordersWithProof = PrintOrder::whereNotNull('payment_proof')
                                ->orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get(['id', 'order_code', 'payment_proof', 'payment_method']);
    
    if ($ordersWithProof->count() > 0) {
        echo "Found {$ordersWithProof->count()} orders with payment proof:\n";
        foreach ($ordersWithProof as $order) {
            echo "  - Order {$order->id}: {$order->order_code} | Method: {$order->payment_method} | Proof: {$order->payment_proof}\n";
        }
    } else {
        echo "No orders found with payment_proof\n";
    }
    
    // 4. Check orders by payment method
    echo "\n4. Checking bank transfer orders:\n";
    $bankTransferOrders = PrintOrder::where('payment_method', 'bank_transfer')
                                   ->orderBy('created_at', 'desc')
                                   ->limit(10)
                                   ->get(['id', 'order_code', 'payment_proof', 'payment_status']);
    
    foreach ($bankTransferOrders as $order) {
        $hasProof = $order->payment_proof ? '✅' : '❌';
        echo "  - Order {$order->id}: {$order->order_code} | Status: {$order->payment_status} | Proof: {$hasProof}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug Complete ===\n";