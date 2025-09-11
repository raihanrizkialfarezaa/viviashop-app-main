<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 FIXING ORDER STATUS WITH CORRECT ENUM VALUES\n";
echo "===============================================\n\n";

// Find the payment_pending order
$pendingOrder = \App\Models\PrintOrder::where('status', 'payment_pending')->first();

if (!$pendingOrder) {
    echo "❌ No payment_pending orders found\n";
    exit;
}

echo "📋 Found order details:\n";
echo "Order ID: {$pendingOrder->id}\n";
echo "Status: {$pendingOrder->status}\n";
echo "Total: Rp " . number_format($pendingOrder->total_price) . "\n";
echo "Created: {$pendingOrder->created_at}\n";
echo "Payment method: {$pendingOrder->payment_method}\n";
echo "Payment status: {$pendingOrder->payment_status}\n\n";

echo "✅ Valid ENUM status values:\n";
echo "- pending_upload\n";
echo "- uploaded\n";
echo "- payment_pending\n";
echo "- payment_confirmed ← Target status\n";
echo "- ready_to_print\n";
echo "- printing\n";
echo "- printed\n";
echo "- completed\n";
echo "- cancelled\n\n";

echo "🔄 Updating order status to 'payment_confirmed'...\n";

try {
    $pendingOrder->update([
        'payment_status' => 'paid',
        'status' => 'payment_confirmed',
        'paid_at' => now()
    ]);
    
    echo "✅ Order payment confirmed successfully!\n";
    
    // Refresh and display updated order
    $updatedOrder = $pendingOrder->fresh();
    echo "\n📊 Updated order status:\n";
    echo "Status: {$updatedOrder->status}\n";
    echo "Payment status: {$updatedOrder->payment_status}\n";
    echo "Paid at: {$updatedOrder->paid_at}\n";
    
} catch (Exception $e) {
    echo "❌ Error updating order: " . $e->getMessage() . "\n";
}

// Check print queue status
echo "\n🖨️ Print queue status:\n";
$paymentConfirmedCount = \App\Models\PrintOrder::where('status', 'payment_confirmed')->count();
$readyToPrintCount = \App\Models\PrintOrder::where('status', 'ready_to_print')->count();
$printingCount = \App\Models\PrintOrder::where('status', 'printing')->count();
$completedCount = \App\Models\PrintOrder::where('status', 'completed')->count();

echo "Payment confirmed: $paymentConfirmedCount\n";
echo "Ready to print: $readyToPrintCount\n";
echo "Currently printing: $printingCount\n";
echo "Completed: $completedCount\n";

if ($paymentConfirmedCount > 0 || $readyToPrintCount > 0) {
    echo "\n✅ Order is now visible in admin print queue!\n";
    echo "Admin can process this order in /admin/print-service/orders\n";
} else {
    echo "\n⚠️ Order not in print queue - may need further investigation\n";
}

?>
