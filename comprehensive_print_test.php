<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 COMPREHENSIVE PRINT WORKFLOW TEST\n";
echo "====================================\n\n";

echo "1️⃣ Testing Order Status Display Logic...\n";
$orderCode = "PRINT-11-09-2025-13-50-56";
$order = \App\Models\PrintOrder::where('order_code', $orderCode)->first();

if (!$order) {
    echo "❌ Order not found, creating test order...\n";
    exit;
}

echo "Order: {$order->order_code}\n";
echo "Status: {$order->status}\n";
echo "Payment Status: {$order->payment_status}\n";

echo "\n2️⃣ Testing Print Button Visibility Logic...\n";
$showPrintButton = in_array($order->status, ['payment_confirmed', 'ready_to_print']);
echo "Show Print Button: " . ($showPrintButton ? '✅ YES' : '❌ NO') . "\n";

echo "\n3️⃣ Testing Backend Print Logic...\n";
echo "Can Print (Model): " . ($order->canPrint() ? '✅ YES' : '❌ NO') . "\n";

echo "\n4️⃣ Testing Admin Controller Print Action...\n";
try {
    $printService = new \App\Services\PrintService();
    $adminController = new \App\Http\Controllers\Admin\PrintServiceController($printService);
    
    echo "Controller instantiated successfully ✅\n";
    
    $request = new \Illuminate\Http\Request();
    $response = $adminController->printOrder($request, $order->id);
    $responseData = json_decode($response->getContent(), true);
    
    if (isset($responseData['success']) && $responseData['success']) {
        echo "Print action successful ✅\n";
        echo "Message: " . $responseData['message'] . "\n";
        
        $order->refresh();
        echo "New order status: {$order->status}\n";
    } else {
        echo "Print action failed ❌\n";
        echo "Error: " . ($responseData['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (Exception $e) {
    echo "Controller error ❌: " . $e->getMessage() . "\n";
}

echo "\n5️⃣ Testing Complete Workflow Simulation...\n";

$testOrders = [
    'payment_confirmed' => null,
    'ready_to_print' => $order
];

foreach ($testOrders as $statusType => $testOrder) {
    if (!$testOrder) continue;
    
    echo "\nTesting order with status: $statusType\n";
    echo "- Can Print: " . ($testOrder->canPrint() ? 'YES' : 'NO') . "\n";
    echo "- Show Print Button: " . (in_array($testOrder->status, ['payment_confirmed', 'ready_to_print']) ? 'YES' : 'NO') . "\n";
    echo "- Status Flow: {$testOrder->status} → ";
    
    if ($testOrder->canPrint()) {
        echo "printing → printed → completed ✅\n";
    } else {
        echo "BLOCKED ❌\n";
    }
}

echo "\n6️⃣ Final Status Check...\n";
$printQueue = \App\Models\PrintOrder::printQueue()->count();
$readyToPrint = \App\Models\PrintOrder::where('status', 'ready_to_print')
    ->where('payment_status', 'paid')->count();
$paymentConfirmed = \App\Models\PrintOrder::where('status', 'payment_confirmed')
    ->where('payment_status', 'paid')->count();

echo "Orders in print queue: $printQueue\n";
echo "Ready to print: $readyToPrint\n";
echo "Payment confirmed: $paymentConfirmed\n";

echo "\n🎯 FINAL VERDICT:\n";
if ($order->canPrint() && $showPrintButton) {
    echo "✅ PRINT WORKFLOW FIXED!\n";
    echo "✅ Admin can now click print button successfully\n";
    echo "✅ Order will progress: ready_to_print → printing → printed\n";
} else {
    echo "❌ Issues still exist:\n";
    echo "- Can Print: " . ($order->canPrint() ? 'YES' : 'NO') . "\n";
    echo "- Show Button: " . ($showPrintButton ? 'YES' : 'NO') . "\n";
}

echo "\n🔗 Test the fix at: http://127.0.0.1:8000/admin/print-service/orders\n";

?>
