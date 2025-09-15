<?php

require_once 'vendor/autoload.php';

use App\Models\PrintOrder;
use App\Services\PrintService;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Manual Payment Status Update ===\n\n";

$orderCode = 'PRINT-15-09-2025-23-23-27';

try {
    $order = PrintOrder::where('order_code', $orderCode)->first();
    
    if (!$order) {
        echo "❌ Order not found: {$orderCode}\n";
        exit;
    }
    
    echo "📋 Before Update:\n";
    echo "Status: {$order->status}\n";
    echo "Payment Status: {$order->payment_status}\n";
    echo "Files Count: " . $order->files()->count() . "\n\n";
    
    // Update payment status
    echo "🔄 Updating payment status...\n";
    
    $order->update([
        'payment_status' => PrintOrder::PAYMENT_PAID,
        'status' => PrintOrder::STATUS_PAYMENT_CONFIRMED
    ]);
    
    echo "✓ Payment status updated\n";
    
    // Mark as ready to print if files exist
    if ($order->files()->count() > 0) {
        $printService = app(PrintService::class);
        $printService->markReadyToPrint($order);
        echo "✓ Marked as ready to print\n";
    }
    
    // Refresh and show updated status
    $order->refresh();
    
    echo "\n📋 After Update:\n";
    echo "Status: {$order->status}\n";
    echo "Payment Status: {$order->payment_status}\n";
    echo "Updated At: {$order->updated_at}\n\n";
    
    echo "🎯 Result: Order payment status telah diupdate!\n";
    echo "✅ Admin sekarang dapat melihat file untuk dicetak\n";
    echo "✅ Status berubah menjadi 'paid' dan 'ready_to_print'\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Update Complete ===\n";