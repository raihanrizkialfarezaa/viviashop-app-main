<?php

require_once 'vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\PrintOrder;
use App\Models\ProductVariant;
use App\Http\Controllers\Admin\PrintServiceController;
use App\Services\PrintService;
use App\Services\StockService;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTING ADMIN CONFIRM PAYMENT FIX ===\n\n";

// Get Order 66 (the problematic order)
$order = PrintOrder::where('order_code', 'PRINT-16-09-2025-00-36-19')->first();
if (!$order) {
    echo "❌ Order PRINT-16-09-2025-00-36-19 not found!\n";
    exit;
}

echo "📋 ORDER DETAILS:\n";
echo "- Order Code: {$order->order_code}\n";
echo "- Payment Method: {$order->payment_method}\n";
echo "- Payment Status: {$order->payment_status}\n";
echo "- Status: {$order->status}\n";
echo "- Paid At: " . ($order->paid_at ?? 'NULL') . "\n";
echo "- Required Stock: " . ($order->total_pages * $order->quantity) . " units\n\n";

// Check current stock before test
$variant = ProductVariant::find($order->paper_variant_id);
$currentStock = $variant ? $variant->stock : 0;
echo "📦 CURRENT STOCK: {$currentStock} units\n\n";

// Check if order is eligible for confirmation
if ($order->payment_status === PrintOrder::PAYMENT_PAID) {
    echo "⚠️  Order already paid, setting back to PAYMENT_WAITING for test...\n";
    $order->update([
        'payment_status' => PrintOrder::PAYMENT_WAITING,
        'status' => PrintOrder::STATUS_PAYMENT_PENDING,
        'paid_at' => null
    ]);
    echo "✅ Order reset to PAYMENT_WAITING\n\n";
}

// Test Admin Controller confirmPayment (NEW VERSION)
echo "🔧 TESTING ADMIN CONFIRM PAYMENT (NEW VERSION):\n";
try {
    $printService = app(PrintService::class);
    $controller = new PrintServiceController($printService);
    
    $request = new Request();
    $response = $controller->confirmPayment($request, $order->id);
    
    $responseData = json_decode($response->getContent(), true);
    echo "✅ Admin confirmPayment Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
    
    // Check order status after confirmation
    $order->refresh();
    echo "📋 ORDER AFTER CONFIRMATION:\n";
    echo "- Payment Status: {$order->payment_status}\n";
    echo "- Status: {$order->status}\n";
    echo "- Paid At: " . ($order->paid_at ?? 'NULL') . "\n\n";
    
    // Check stock after confirmation
    $variant->refresh();
    $stockAfter = $variant->stock;
    $stockReduced = $currentStock - $stockAfter;
    echo "📦 STOCK AFTER CONFIRMATION: {$stockAfter} units\n";
    echo "📉 STOCK REDUCED: {$stockReduced} units\n";
    echo "🎯 EXPECTED REDUCTION: " . ($order->total_pages * $order->quantity) . " units\n\n";
    
    if ($stockReduced == ($order->total_pages * $order->quantity)) {
        echo "✅ STOCK REDUCTION SUCCESSFUL! ✅\n";
    } else {
        echo "❌ STOCK REDUCTION MISMATCH!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETED ===\n";