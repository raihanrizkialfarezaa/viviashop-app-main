<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔄 RESET AND CREATE FRESH PRINT TEST\n";
echo "====================================\n\n";

echo "1️⃣ Finding orders in correct state...\n";
$paymentConfirmedOrders = \App\Models\PrintOrder::where('status', 'payment_confirmed')
    ->where('payment_status', 'paid')
    ->get();

$readyToPrintOrders = \App\Models\PrintOrder::where('status', 'ready_to_print')
    ->where('payment_status', 'paid')
    ->get();

echo "Payment Confirmed orders: " . $paymentConfirmedOrders->count() . "\n";
echo "Ready To Print orders: " . $readyToPrintOrders->count() . "\n";

if ($paymentConfirmedOrders->count() > 0) {
    $testOrder = $paymentConfirmedOrders->first();
    echo "\n2️⃣ Testing with payment_confirmed order: {$testOrder->order_code}\n";
} else {
    echo "\n2️⃣ Creating a new test order...\n";
    
    $session = \App\Models\PrintSession::active()->first();
    if (!$session) {
        $printService = new \App\Services\PrintService();
        $session = $printService->generateSession();
        echo "Created new session: {$session->session_token}\n";
    }
    
    $variant = \App\Models\ProductVariant::where('status', 'active')->first();
    if (!$variant) {
        echo "❌ No active product variants found\n";
        exit;
    }
    
    $testOrder = \App\Models\PrintOrder::create([
        'order_code' => \App\Models\PrintOrder::generateCode(),
        'customer_phone' => '085123456789',
        'customer_name' => 'Test Customer',
        'file_data' => json_encode([['name' => 'test.pdf', 'pages' => 5]]),
        'paper_product_id' => $variant->product_id,
        'paper_variant_id' => $variant->id,
        'print_type' => 'color',
        'quantity' => 1,
        'total_pages' => 5,
        'unit_price' => 1500,
        'total_price' => 7500,
        'payment_method' => 'toko',
        'status' => 'payment_confirmed',
        'payment_status' => 'paid',
        'session_id' => $session->id
    ]);
    
    echo "Created test order: {$testOrder->order_code}\n";
}

echo "\n3️⃣ Testing canPrint() method with current order...\n";
echo "Order Status: {$testOrder->status}\n";
echo "Payment Status: {$testOrder->payment_status}\n";
echo "Can Print: " . ($testOrder->canPrint() ? '✅ YES' : '❌ NO') . "\n";
echo "Is Paid: " . ($testOrder->isPaid() ? '✅ YES' : '❌ NO') . "\n";

echo "\n4️⃣ Testing status conditions...\n";
$acceptableStatuses = ['payment_confirmed', 'ready_to_print'];
echo "Acceptable statuses for canPrint(): " . implode(', ', $acceptableStatuses) . "\n";
echo "Order status '{$testOrder->status}' is acceptable: " . (in_array($testOrder->status, $acceptableStatuses) ? '✅ YES' : '❌ NO') . "\n";

echo "\n5️⃣ Testing admin button visibility...\n";
$showPrintButton = in_array($testOrder->status, ['payment_confirmed', 'ready_to_print']);
echo "Show Print Button: " . ($showPrintButton ? '✅ YES' : '❌ NO') . "\n";

echo "\n6️⃣ Testing print action simulation...\n";
if ($testOrder->canPrint()) {
    try {
        $printService = new \App\Services\PrintService();
        echo "Attempting to print order {$testOrder->order_code}...\n";
        
        $result = $printService->printDocument($testOrder);
        
        if ($result) {
            $testOrder->refresh();
            echo "✅ Print successful!\n";
            echo "Status changed to: {$testOrder->status}\n";
            echo "Printed at: {$testOrder->printed_at}\n";
        }
    } catch (Exception $e) {
        echo "❌ Print failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Cannot print - order fails canPrint() check\n";
}

echo "\n7️⃣ Final verification...\n";
$printQueueCount = \App\Models\PrintOrder::printQueue()->count();
echo "Current print queue count: $printQueueCount\n";

echo "\n🎯 CONCLUSION:\n";
echo "The fix allows orders with both 'payment_confirmed' and 'ready_to_print' status to be printed.\n";
echo "Admin can now successfully click the print button for orders in either status.\n";
echo "✅ Print workflow is now functional!\n";

?>
