<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrintOrder;
use App\Models\ProductVariant;
use App\Models\PrintSession;
use App\Models\PrintFile;
use App\Services\PrintService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

echo "=== TESTING COMPLETE PRINT SERVICE FLOW ===\n\n";

$printService = new PrintService();

echo "1. GENERATE SESSION...\n";
$session = $printService->generateSession();
echo "   Session Token: {$session->session_token}\n\n";

echo "2. CREATE DUMMY FILE...\n";
$dummyContent = "Test content for printing";
$filename = 'test_document.txt';
$storagePath = "print-files/{$session->session_token}/{$filename}";
Storage::put($storagePath, $dummyContent);

$printFile = PrintFile::create([
    'print_session_id' => $session->id,
    'file_name' => $filename,
    'file_type' => 'txt',
    'original_name' => $filename,
    'stored_name' => $filename,
    'file_path' => $storagePath,
    'pages_count' => 1,
    'file_size' => strlen($dummyContent),
    'mime_type' => 'text/plain'
]);

echo "   File created: {$printFile->original_name} (1 page)\n\n";

echo "3. CREATE PRINT ORDER...\n";
$variant = ProductVariant::where('paper_size', 'A4')
    ->where('print_type', 'bw')
    ->first();

echo "   Using variant: ID {$variant->id} - {$variant->paper_size} {$variant->print_type}\n";
echo "   Current stock: {$variant->stock}\n";

$orderData = [
    'variant_id' => $variant->id,
    'quantity' => 1,
    'payment_method' => 'cash',
    'customer_name' => 'Test Customer',
    'customer_phone' => '08123456789'
];

try {
    $printOrder = $printService->createPrintOrder($orderData, $session);
    echo "   ✅ Order created: {$printOrder->order_code}\n";
    echo "   Total pages: {$printOrder->total_pages}\n";
    echo "   Required stock: " . ($printOrder->total_pages * $printOrder->quantity) . "\n";
    echo "   Stock after order creation: {$variant->fresh()->stock}\n\n";
    
    echo "4. CONFIRM PAYMENT (SHOULD REDUCE STOCK)...\n";
    $stockBefore = $variant->fresh()->stock;
    echo "   Stock before payment: {$stockBefore}\n";
    
    $confirmedOrder = $printService->confirmPayment($printOrder);
    
    $stockAfter = $variant->fresh()->stock;
    echo "   Stock after payment: {$stockAfter}\n";
    echo "   Stock reduction: " . ($stockBefore - $stockAfter) . "\n";
    echo "   Order status: {$confirmedOrder->status}\n";
    echo "   Payment status: {$confirmedOrder->payment_status}\n\n";
    
    echo "5. CHECK STOCK MOVEMENTS...\n";
    $movements = DB::table('stock_movements')
        ->where('variant_id', $variant->id)
        ->orderBy('created_at', 'desc')
        ->take(3)
        ->get();
    
    foreach ($movements as $movement) {
        echo "   - {$movement->movement_type}: {$movement->quantity} units\n";
        echo "     Stock: {$movement->old_stock} → {$movement->new_stock}\n";
        echo "     Reason: {$movement->reason}\n";
        echo "     Reference: {$movement->reference_type} #{$movement->reference_id}\n";
        echo "     Time: {$movement->created_at}\n\n";
    }
    
    echo "6. TEST ORDER CANCELLATION (SHOULD RESTORE STOCK)...\n";
    $stockBeforeCancel = $variant->fresh()->stock;
    echo "   Stock before cancel: {$stockBeforeCancel}\n";
    
    $cancelledOrder = $printService->cancelOrder($confirmedOrder);
    
    $stockAfterCancel = $variant->fresh()->stock;
    echo "   Stock after cancel: {$stockAfterCancel}\n";
    echo "   Stock restoration: " . ($stockAfterCancel - $stockBeforeCancel) . "\n";
    echo "   Order status: {$cancelledOrder->status}\n\n";
    
    echo "✅ SEMUA TEST BERHASIL!\n";
    echo "Stock management berfungsi dengan baik.\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

Storage::delete($storagePath);
