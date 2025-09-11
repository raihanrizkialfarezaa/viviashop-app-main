<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 DEBUGGING PRINT STATUS ISSUE\n";
echo "===============================\n\n";

$orderCode = "PRINT-11-09-2025-13-50-56";
$order = \App\Models\PrintOrder::where('order_code', $orderCode)->first();

if (!$order) {
    echo "❌ Order not found: $orderCode\n";
    exit;
}

echo "📋 Order Details:\n";
echo "Order Code: {$order->order_code}\n";
echo "Status: {$order->status}\n";
echo "Payment Status: {$order->payment_status}\n";
echo "Can Print: " . ($order->canPrint() ? 'YES' : 'NO') . "\n";
echo "Is Paid: " . ($order->isPaid() ? 'YES' : 'NO') . "\n\n";

echo "🔧 Status Constants:\n";
echo "STATUS_PAYMENT_CONFIRMED: " . \App\Models\PrintOrder::STATUS_PAYMENT_CONFIRMED . "\n";
echo "STATUS_READY_TO_PRINT: " . \App\Models\PrintOrder::STATUS_READY_TO_PRINT . "\n";
echo "PAYMENT_PAID: " . \App\Models\PrintOrder::PAYMENT_PAID . "\n\n";

echo "🎯 Expected vs Actual:\n";
echo "Expected status for canPrint(): " . \App\Models\PrintOrder::STATUS_PAYMENT_CONFIRMED . "\n";
echo "Actual order status: {$order->status}\n";
echo "Expected payment status: " . \App\Models\PrintOrder::PAYMENT_PAID . "\n";
echo "Actual payment status: {$order->payment_status}\n\n";

if ($order->status === 'ready_to_print' && $order->payment_status === 'paid') {
    echo "⚠️ ISSUE IDENTIFIED:\n";
    echo "Order status is 'ready_to_print' but canPrint() expects 'payment_confirmed'\n";
    echo "This means the status transition logic is inconsistent.\n\n";
    
    echo "🔄 Analyzing canPrint() method logic...\n";
    echo "Current canPrint() requires:\n";
    echo "1. isPaid() = true ✅\n";
    echo "2. status = 'payment_confirmed' ❌ (current: '{$order->status}')\n\n";
    
    echo "💡 SOLUTION OPTIONS:\n";
    echo "Option 1: Update canPrint() to accept both 'payment_confirmed' AND 'ready_to_print'\n";
    echo "Option 2: Fix status transition to go 'payment_confirmed' → 'ready_to_print' on admin action\n";
    echo "Option 3: Keep order in 'payment_confirmed' until print button is clicked\n\n";
}

echo "📊 All orders with this status pattern:\n";
$similarOrders = \App\Models\PrintOrder::where('status', 'ready_to_print')
    ->where('payment_status', 'paid')
    ->get();

foreach ($similarOrders as $similarOrder) {
    echo "- {$similarOrder->order_code}: {$similarOrder->status} / {$similarOrder->payment_status}\n";
}

?>
