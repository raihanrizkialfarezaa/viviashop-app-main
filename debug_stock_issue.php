<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrintOrder;
use App\Models\ProductVariant;
use App\Services\PrintService;
use Illuminate\Support\Facades\DB;

echo "=== ANALISIS MASALAH STOCK MANAGEMENT ===\n\n";

$variant = ProductVariant::with('product')
    ->where('product_id', 135)
    ->where('paper_size', 'A4')
    ->where('print_type', 'bw')
    ->first();

if (!$variant) {
    echo "❌ VARIANT TIDAK DITEMUKAN\n";
    echo "- Product ID: 135\n";
    echo "- Paper Size: A4\n";
    echo "- Print Type: bw\n\n";
    
    echo "MENCARI VARIANT YANG ADA:\n";
    $variants = ProductVariant::where('product_id', 135)->get();
    foreach ($variants as $v) {
        echo "- ID: {$v->id} | Size: {$v->paper_size} | Type: {$v->print_type} | Stock: {$v->stock}\n";
    }
    exit;
}

echo "✅ VARIANT DITEMUKAN:\n";
echo "- ID: {$variant->id}\n";
echo "- Product: {$variant->product->name}\n";
echo "- Size: {$variant->paper_size}\n";
echo "- Type: {$variant->print_type}\n";
echo "- Current Stock: {$variant->stock}\n\n";

echo "=== MEMERIKSA ORDERS TERBARU ===\n";
$recentOrders = PrintOrder::with('variant')
    ->where('paper_variant_id', $variant->id)
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

echo "ORDERS MENGGUNAKAN VARIANT INI (5 terbaru):\n";
foreach ($recentOrders as $order) {
    echo "- Order: {$order->order_code}\n";
    echo "  Status: {$order->status}\n";
    echo "  Payment: {$order->payment_status}\n";
    echo "  Pages: {$order->total_pages}\n";
    echo "  Quantity: {$order->quantity}\n";
    echo "  Required Stock: " . ($order->total_pages * $order->quantity) . "\n";
    echo "  Created: {$order->created_at}\n\n";
}

echo "=== MEMERIKSA STOCK MOVEMENTS ===\n";
$stockMovements = DB::table('stock_movements')
    ->where('variant_id', $variant->id)
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get();

echo "STOCK MOVEMENTS (10 terbaru):\n";
foreach ($stockMovements as $movement) {
    echo "- Type: {$movement->movement_type}\n";
    echo "  Quantity: {$movement->quantity}\n";
    echo "  Old Stock: {$movement->old_stock}\n";
    echo "  New Stock: {$movement->new_stock}\n";
    echo "  Reason: {$movement->reason}\n";
    echo "  Reference: {$movement->reference_type} #{$movement->reference_id}\n";
    echo "  Time: {$movement->created_at}\n\n";
}

echo "=== TESTING STOCK REDUCTION ===\n";
$printService = new PrintService();

$testOrder = PrintOrder::where('paper_variant_id', $variant->id)
    ->where('payment_status', 'waiting')
    ->first();

if ($testOrder) {
    echo "✅ DITEMUKAN TEST ORDER: {$testOrder->order_code}\n";
    echo "- Current Stock Before: {$variant->fresh()->stock}\n";
    
    try {
        echo "- Attempting payment confirmation...\n";
        $result = $printService->confirmPayment($testOrder);
        
        echo "- Payment confirmed successfully!\n";
        echo "- Current Stock After: {$variant->fresh()->stock}\n";
        
    } catch (\Exception $e) {
        echo "❌ PAYMENT CONFIRMATION FAILED: " . $e->getMessage() . "\n";
    }
} else {
    echo "ℹ️ TIDAK ADA ORDER WAITING UNTUK TESTING\n";
}

echo "=== DIAGNOSIS MASALAH ===\n";

$isPrintService = $variant->product->is_print_service ?? false;
echo "- Product is_print_service: " . ($isPrintService ? 'YES' : 'NO') . "\n";

$stockService = app(\App\Services\StockManagementService::class);
echo "- StockManagementService available: YES\n";

$hasStockMovementsTable = DB::select("SHOW TABLES LIKE 'stock_movements'");
echo "- stock_movements table exists: " . (count($hasStockMovementsTable) > 0 ? 'YES' : 'NO') . "\n";

echo "\n=== KESIMPULAN ===\n";
if (count($stockMovements) == 0) {
    echo "❌ MASALAH: Tidak ada stock movements tercatat\n";
    echo "- Kemungkinan payment confirmation tidak memanggil stock reduction\n";
    echo "- Atau stock reduction gagal tanpa error handling yang proper\n";
} else {
    echo "✅ Stock movements ada, mungkin ada masalah di flow lain\n";
}
