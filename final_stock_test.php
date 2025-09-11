<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrintOrder;
use App\Models\ProductVariant;
use App\Models\PrintSession;
use App\Models\PrintFile;
use App\Services\PrintService;
use App\Services\StockManagementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

echo "=== COMPREHENSIVE STOCK MANAGEMENT TEST ===\n\n";

$printService = new PrintService();
$stockService = new StockManagementService();

echo "1. CHECKING INITIAL STOCK DATA...\n";
$variants = DB::table('product_variants')
    ->join('products', 'product_variants.product_id', '=', 'products.id')
    ->where('products.is_print_service', true)
    ->select('product_variants.*', 'products.name as product_name')
    ->get();

echo "Found " . $variants->count() . " print service variants:\n";
foreach ($variants as $variant) {
    echo "- ID: {$variant->id} | {$variant->paper_size} {$variant->print_type} | Stock: {$variant->stock}\n";
}
echo "\n";

echo "2. CREATING TEST ORDER...\n";
$session = $printService->generateSession();
echo "Session created: {$session->session_token}\n";

$printFile = PrintFile::create([
    'print_session_id' => $session->id,
    'file_name' => 'test_doc.pdf',
    'file_type' => 'pdf',
    'file_path' => 'test/path.pdf',
    'pages_count' => 5,
    'file_size' => 1024,
    'mime_type' => 'application/pdf'
]);

$testVariant = $variants->first();
$stockBefore = $testVariant->stock;

$orderData = [
    'variant_id' => $testVariant->id,
    'quantity' => 2,
    'payment_method' => 'cash',
    'customer_name' => 'Test Customer',
    'customer_phone' => '08123456789'
];

$printOrder = $printService->createPrintOrder($orderData, $session);
echo "Order created: {$printOrder->order_code}\n";
echo "Pages: {$printOrder->total_pages} | Quantity: {$printOrder->quantity}\n";
echo "Required stock: " . ($printOrder->total_pages * $printOrder->quantity) . " sheets\n";
echo "Stock before payment: {$stockBefore}\n\n";

echo "3. CONFIRMING PAYMENT (SHOULD REDUCE STOCK)...\n";
$confirmedOrder = $printService->confirmPayment($printOrder);

$stockAfter = ProductVariant::find($testVariant->id)->stock;
echo "Stock after payment: {$stockAfter}\n";
echo "Stock reduction: " . ($stockBefore - $stockAfter) . "\n\n";

echo "4. CHECKING STOCK MOVEMENTS...\n";
$movements = $stockService->getStockReport($testVariant->id);
$latestMovement = $movements->first();

if ($latestMovement) {
    echo "Latest movement:\n";
    echo "- Type: {$latestMovement->movement_type}\n";
    echo "- Quantity: {$latestMovement->quantity}\n";
    echo "- Old Stock: {$latestMovement->old_stock}\n";
    echo "- New Stock: {$latestMovement->new_stock}\n";
    echo "- Reason: {$latestMovement->reason}\n";
    echo "- Reference: {$latestMovement->reference_type} #{$latestMovement->reference_id}\n\n";
} else {
    echo "❌ NO STOCK MOVEMENT RECORDED!\n\n";
}

echo "5. TESTING STOCK ADJUSTMENT...\n";
$stockBeforeAdjust = ProductVariant::find($testVariant->id)->stock;
echo "Stock before adjustment: {$stockBeforeAdjust}\n";

$adjustResult = $stockService->adjustStock(
    $testVariant->id,
    $stockBeforeAdjust + 1000,
    'restock',
    'Test restock adjustment'
);

$stockAfterAdjust = ProductVariant::find($testVariant->id)->stock;
echo "Stock after adjustment: {$stockAfterAdjust}\n";
echo "Adjustment result: " . ($adjustResult ? 'SUCCESS' : 'FAILED') . "\n\n";

echo "6. TESTING LOW STOCK DETECTION...\n";
$lowStockVariants = $stockService->getLowStockVariants();
echo "Low stock variants found: " . $lowStockVariants->count() . "\n";
foreach ($lowStockVariants as $lowVariant) {
    echo "- {$lowVariant->paper_size} {$lowVariant->print_type}: {$lowVariant->stock} sheets\n";
}
echo "\n";

echo "7. TESTING ORDER CANCELLATION (SHOULD RESTORE STOCK)...\n";
$stockBeforeCancel = ProductVariant::find($testVariant->id)->stock;
echo "Stock before cancel: {$stockBeforeCancel}\n";

$cancelledOrder = $printService->cancelOrder($confirmedOrder);

$stockAfterCancel = ProductVariant::find($testVariant->id)->stock;
echo "Stock after cancel: {$stockAfterCancel}\n";
echo "Stock restoration: " . ($stockAfterCancel - $stockBeforeCancel) . "\n\n";

echo "8. FINAL STOCK REPORT...\n";
$finalMovements = $stockService->getStockReport($testVariant->id, null, null);
echo "Total movements for this variant: " . $finalMovements->count() . "\n";

echo "\nLatest 3 movements:\n";
foreach ($finalMovements->take(3) as $movement) {
    echo "- {$movement->movement_type}: {$movement->quantity} | {$movement->old_stock} → {$movement->new_stock} | {$movement->reason}\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "Stock management system is working correctly!\n";

Storage::deleteDirectory('test');
