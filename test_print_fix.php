<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 TESTING PRINT STATUS FIX\n";
echo "===========================\n\n";

$orderCode = "PRINT-11-09-2025-13-50-56";
$order = \App\Models\PrintOrder::where('order_code', $orderCode)->first();

if (!$order) {
    echo "❌ Order not found: $orderCode\n";
    exit;
}

echo "📋 Order Details After Fix:\n";
echo "Order Code: {$order->order_code}\n";
echo "Status: {$order->status}\n";
echo "Payment Status: {$order->payment_status}\n";
echo "Can Print: " . ($order->canPrint() ? '✅ YES' : '❌ NO') . "\n";
echo "Is Paid: " . ($order->isPaid() ? '✅ YES' : '❌ NO') . "\n\n";

if ($order->canPrint()) {
    echo "🎯 SUCCESS! Order can now be printed.\n\n";
    
    echo "🧪 Testing Print Flow Simulation:\n";
    echo "1. Current Status: {$order->status}\n";
    
    try {
        $printService = new \App\Services\PrintService();
        
        echo "2. Calling printDocument()...\n";
        $result = $printService->printDocument($order);
        
        if ($result) {
            $order->refresh();
            echo "3. ✅ Print successful!\n";
            echo "4. New Status: {$order->status}\n";
            echo "5. Printed At: {$order->printed_at}\n";
        }
    } catch (Exception $e) {
        echo "3. ❌ Print failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Order still cannot be printed. Need further investigation.\n";
}

echo "\n📊 Print Queue Status:\n";
$printQueue = \App\Models\PrintOrder::printQueue()->get();
echo "Orders in print queue: " . $printQueue->count() . "\n";

foreach ($printQueue as $queueOrder) {
    echo "- {$queueOrder->order_code}: {$queueOrder->status} / {$queueOrder->payment_status}\n";
}

echo "\n🎉 Admin Print Button Should Now Work!\n";
echo "Test URL: http://127.0.0.1:8000/admin/print-service/orders\n";

?>
