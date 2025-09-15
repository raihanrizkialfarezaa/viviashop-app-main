<?php

require_once 'vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\PrintOrder;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Http\Controllers\Admin\PrintServiceController;
use App\Services\PrintService;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DEBUG ADMIN CONFIRM PAYMENT ===\n\n";

// Get Order 66
$order = PrintOrder::where('order_code', 'PRINT-16-09-2025-00-36-19')->first();
if (!$order) {
    echo "❌ Order not found!\n";
    exit;
}

echo "📋 ORDER DETAILS:\n";
echo "- ID: {$order->id}\n";
echo "- Order Code: {$order->order_code}\n";
echo "- Payment Method: {$order->payment_method}\n";
echo "- Payment Status: {$order->payment_status}\n";
echo "- Status: {$order->status}\n";
echo "- Paper Product ID: {$order->paper_product_id}\n";
echo "- Paper Variant ID: {$order->paper_variant_id}\n";
echo "- Required Stock: " . ($order->total_pages * $order->quantity) . " units\n\n";

// Check existing stock movements for this order
echo "📊 EXISTING STOCK MOVEMENTS FOR THIS ORDER:\n";
$movements = StockMovement::where('reference', 'like', "%{$order->order_code}%")->get();
foreach ($movements as $movement) {
    echo "- {$movement->type}: {$movement->quantity} units, Reason: {$movement->reason}\n";
}
if ($movements->isEmpty()) {
    echo "- No movements found\n";
}
echo "\n";

// Reset order to PAYMENT_WAITING
echo "🔄 RESETTING ORDER TO PAYMENT_WAITING...\n";
$order->update([
    'payment_status' => PrintOrder::PAYMENT_WAITING,
    'status' => PrintOrder::STATUS_PAYMENT_PENDING,
    'paid_at' => null
]);

// Check current stock
$variant = ProductVariant::find($order->paper_variant_id);
$stockBefore = $variant->stock;
echo "📦 STOCK BEFORE: {$stockBefore} units\n\n";

// TEST DIRECTLY CALLING PRINTSERVICE CONFIRMPAYMENT
echo "🔧 TESTING PRINTSERVICE confirmPayment DIRECTLY:\n";
try {
    $printService = app(PrintService::class);
    
    // Enable logging for debugging
    Log::info("DEBUG: About to call confirmPayment for order {$order->order_code}");
    
    $result = $printService->confirmPayment($order);
    
    echo "✅ PrintService confirmPayment result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    // Check order after
    $order->refresh();
    echo "📋 ORDER AFTER:\n";
    echo "- Payment Status: {$order->payment_status}\n";
    echo "- Status: {$order->status}\n";
    echo "- Paid At: " . ($order->paid_at ?? 'NULL') . "\n\n";
    
    // Check stock after
    $variant->refresh();
    $stockAfter = $variant->stock;
    $stockReduced = $stockBefore - $stockAfter;
    echo "📦 STOCK AFTER: {$stockAfter} units\n";
    echo "📉 STOCK REDUCED: {$stockReduced} units\n";
    echo "🎯 EXPECTED: " . ($order->total_pages * $order->quantity) . " units\n\n";
    
    // Check new stock movements
    echo "📊 NEW STOCK MOVEMENTS:\n";
    $newMovements = StockMovement::where('reference', 'like', "%{$order->order_code}%")->get();
    foreach ($newMovements as $movement) {
        echo "- ID: {$movement->id}, {$movement->type}: {$movement->quantity} units, Reason: {$movement->reason}, Created: {$movement->created_at}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== DEBUG COMPLETED ===\n";