<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 FIXING ORDER STATUS PROGRESSION\n";
echo "===================================\n\n";

// Find the payment_pending order
$pendingOrder = \App\Models\PrintOrder::where('status', 'payment_pending')->first();

if (!$pendingOrder) {
    echo "❌ No payment_pending orders found\n";
    exit;
}

echo "📋 Found order details:\n";
echo "Order ID: {$pendingOrder->id}\n";
echo "Status: {$pendingOrder->status}\n";
echo "Total: Rp " . number_format($pendingOrder->total_amount) . "\n";
echo "Created: {$pendingOrder->created_at}\n";
echo "Payment method: {$pendingOrder->payment_method}\n";
echo "Payment status: {$pendingOrder->payment_status}\n\n";

// Check if this is a cash payment that should be auto-confirmed
if ($pendingOrder->payment_method === 'cash') {
    echo "💰 Cash payment detected - auto-confirming payment...\n";
    
    $pendingOrder->update([
        'payment_status' => 'paid',
        'status' => 'confirmed',
        'paid_at' => now()
    ]);
    
    echo "✅ Order payment confirmed and status updated to 'confirmed'\n";
} else {
    echo "💳 Non-cash payment - manual confirmation required\n";
    echo "Available actions:\n";
    echo "1. Confirm payment (if payment received)\n";
    echo "2. Cancel order (if payment failed)\n\n";
    
    // For demo purposes, let's confirm it anyway
    echo "🔄 Confirming payment for demo...\n";
    $pendingOrder->update([
        'payment_status' => 'paid',
        'status' => 'confirmed',
        'paid_at' => now()
    ]);
    echo "✅ Payment confirmed\n";
}

echo "\n📊 Updated order status:\n";
$updatedOrder = $pendingOrder->fresh();
echo "Status: {$updatedOrder->status}\n";
echo "Payment status: {$updatedOrder->payment_status}\n";
echo "Paid at: {$updatedOrder->paid_at}\n";

// Check print queue status
echo "\n🖨️ Print queue status:\n";
$printQueueCount = \App\Models\PrintOrder::where('status', 'confirmed')->count();
$printingCount = \App\Models\PrintOrder::where('status', 'printing')->count();
$completedCount = \App\Models\PrintOrder::where('status', 'completed')->count();

echo "Confirmed (ready to print): $printQueueCount\n";
echo "Currently printing: $printingCount\n";
echo "Completed: $completedCount\n";

if ($printQueueCount > 0) {
    echo "\n✅ Order is now in the print queue!\n";
    echo "Admin can now see this order in /admin/print-service/orders\n";
} else {
    echo "\n⚠️ Order not in print queue - may need further investigation\n";
}

?>
