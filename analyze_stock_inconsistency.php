<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ANALISIS KETIDAKSINKRONAN STOK AMPLOP ===\n";

$amplop = \App\Models\Product::with(['productInventory', 'productVariants'])->find(9);

echo "\n1. DATA AMPLOP DARI BERBAGAI SUMBER\n";
echo "Product ID: {$amplop->id}\n";
echo "Product Name: {$amplop->name}\n";

echo "\n2. PRODUCT INVENTORY (Table: product_inventories)\n";
if ($amplop->productInventory) {
    echo "ProductInventory qty: {$amplop->productInventory->qty}\n";
} else {
    echo "No ProductInventory record found\n";
}

echo "\n3. PRODUCT VARIANTS (Table: product_variants)\n";
if ($amplop->productVariants->count() > 0) {
    foreach ($amplop->productVariants as $variant) {
        echo "Variant {$variant->id}: stock = {$variant->stock}\n";
    }
    $totalVariantStock = $amplop->productVariants->sum('stock');
    echo "Total variant stock: {$totalVariantStock}\n";
} else {
    echo "No variants found\n";
}

echo "\n4. STOCK MOVEMENTS (Table: stock_movements)\n";
$stockMovements = \App\Models\StockMovement::whereHas('variant', function($q) {
    $q->where('product_id', 9);
})->orderBy('created_at', 'desc')->get();

echo "StockMovement records: {$stockMovements->count()}\n";
$totalIn = 0;
$totalOut = 0;
foreach ($stockMovements as $movement) {
    echo "- {$movement->created_at}: {$movement->movement_type} {$movement->quantity} (old: {$movement->old_stock} -> new: {$movement->new_stock})\n";
    if ($movement->movement_type === 'in') {
        $totalIn += $movement->quantity;
    } else {
        $totalOut += $movement->quantity;
    }
}
echo "Total IN: {$totalIn}, Total OUT: {$totalOut}, Net: " . ($totalIn - $totalOut) . "\n";

echo "\n5. REKAMAN STOK (Table: rekaman_stoks)\n";
$rekamanStok = \App\Models\RekamanStok::where('product_id', 9)->orderBy('created_at', 'desc')->get();
echo "RekamanStok records: {$rekamanStok->count()}\n";
foreach ($rekamanStok as $rekaman) {
    $masuk = $rekaman->stok_masuk ?? 0;
    $awal = $rekaman->stok_awal ?? 0;
    $sisa = $rekaman->stok_sisa ?? 0;
    echo "- {$rekaman->created_at}: Masuk={$masuk}, Awal={$awal}, Sisa={$sisa}\n";
}

echo "\n6. PEMBELIAN DETAILS (Table: pembelian_details)\n";
$pembelianDetails = \App\Models\PembelianDetail::where('id_produk', 9)->with('pembelian')->get();
echo "Purchase details: {$pembelianDetails->count()}\n";
$totalPurchased = 0;
foreach ($pembelianDetails as $detail) {
    $status = $detail->pembelian->status ?? 'unknown';
    echo "- Purchase {$detail->id_pembelian}: qty={$detail->jumlah}, status={$status}\n";
    if ($status === 'completed') {
        $totalPurchased += $detail->jumlah;
    }
}
echo "Total purchased (completed): {$totalPurchased}\n";

echo "\n7. ORDER DETAILS (Table: order_details)\n";
try {
    $orderDetails = \App\Models\OrderDetail::where('id_produk', 9)->with('order')->get();
    echo "Order details: {$orderDetails->count()}\n";
    $totalSold = 0;
    foreach ($orderDetails as $detail) {
        $status = $detail->order->status ?? 'unknown';
        echo "- Order {$detail->id_order}: qty={$detail->jumlah}, status={$status}\n";
        if (in_array($status, ['completed', 'shipped', 'delivered'])) {
            $totalSold += $detail->jumlah;
        }
    }
    echo "Total sold (completed): {$totalSold}\n";
} catch (Exception $e) {
    echo "OrderDetail model not found or error: {$e->getMessage()}\n";
    $totalSold = 0;
}

echo "\n8. KETIDAKSINKRONAN ANALYSIS\n";
echo "Modal menampilkan: 35 (dari ProductInventory atau variant?)\n";
echo "Page stok menampilkan: 50 (dari mana?)\n";
echo "ProductInventory qty: " . ($amplop->productInventory ? $amplop->productInventory->qty : 'null') . "\n";
echo "Total variant stock: " . ($amplop->productVariants->count() > 0 ? $amplop->productVariants->sum('stock') : 'null') . "\n";

echo "\n=== IDENTIFIKASI MASALAH ===\n";
echo "Kemungkinan penyebab ketidaksinkronan:\n";
echo "1. ProductInventory dan ProductVariant tidak sinkron\n";
echo "2. StockMovement tidak update semua tabel\n";
echo "3. Modal dan halaman stok baca dari tabel berbeda\n";
echo "4. Ada transaksi yang tidak tercatat dengan benar\n";