<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING STOCK ISSUE: AMPLOP SHOULD BE 20, NOT 30 ===\n";

$amplop = \App\Models\Product::find(9);
if (!$amplop) {
    echo "❌ AMPLOP product not found!\n";
    exit;
}

echo "Current AMPLOP data:\n";
echo "- Product ID: {$amplop->id}\n";
echo "- Name: {$amplop->name}\n";

if ($amplop->productInventory) {
    echo "- Current Stock: {$amplop->productInventory->qty}\n";
} else {
    echo "- No product inventory found\n";
}

echo "\n=== CHECKING STOCK MOVEMENTS FOR AMPLOP ===\n";
$stockMovements = \App\Models\StockMovement::where('variant_id', $amplop->id)
                    ->orWhereHas('variant', function($q) {
                        $q->where('product_id', 9);
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();

echo "Found {$stockMovements->count()} stock movements for AMPLOP\n";
foreach ($stockMovements as $movement) {
    echo "- {$movement->created_at}: {$movement->movement_type} {$movement->quantity} ({$movement->reason})\n";
}

echo "\n=== CHECKING REKAMAN STOK FOR AMPLOP ===\n";
$rekamanStok = \App\Models\RekamanStok::where('product_id', 9)
                ->orderBy('created_at', 'desc')
                ->get();

echo "Found {$rekamanStok->count()} rekaman stok entries for AMPLOP\n";
foreach ($rekamanStok as $rekaman) {
    $stok_masuk = $rekaman->stok_masuk ?? 0;
    $stok_awal = $rekaman->stok_awal ?? 0;
    $stok_sisa = $rekaman->stok_sisa ?? 0;
    echo "- {$rekaman->created_at}: Masuk={$stok_masuk}, Awal={$stok_awal}, Sisa={$stok_sisa}\n";
}

echo "\n=== CHECKING PEMBELIAN DETAILS FOR AMPLOP ===\n";
$pembelianDetails = \App\Models\PembelianDetail::where('id_produk', 9)
                     ->with('pembelian')
                     ->orderBy('created_at', 'desc')
                     ->get();

echo "Found {$pembelianDetails->count()} purchase details for AMPLOP\n";
$totalPurchased = 0;
foreach ($pembelianDetails as $detail) {
    echo "- Purchase {$detail->id_pembelian}: Qty={$detail->jumlah}, Date={$detail->created_at}\n";
    $totalPurchased += $detail->jumlah;
}
echo "Total purchased quantity: {$totalPurchased}\n";

echo "\n=== ANALYSIS ===\n";
echo "If AMPLOP started with 10 stock and you purchased 10 more:\n";
echo "Expected final stock: 10 + 10 = 20\n";
echo "Actual stock: {$amplop->productInventory->qty}\n";

if ($amplop->productInventory->qty != 20) {
    echo "❌ Stock mismatch detected!\n";
    
    echo "\n=== CHECKING FOR DUPLICATE STOCK UPDATES ===\n";
    
    $duplicateRekamanStok = \App\Models\RekamanStok::where('product_id', 9)
                             ->whereDate('created_at', today())
                             ->get();
    
    if ($duplicateRekamanStok->count() > 1) {
        echo "⚠️ Found {$duplicateRekamanStok->count()} rekaman stok entries for today - possible duplicates!\n";
        foreach ($duplicateRekamanStok as $rekaman) {
            echo "  - ID {$rekaman->id}: Masuk={$rekaman->stok_masuk}, created at {$rekaman->created_at}\n";
        }
    }
    
    echo "\n=== CHECKING PRODUCT INVENTORY UPDATE LOGIC ===\n";
    
    echo "The issue appears to be in PembelianDetailController store() method.\n";
    echo "Current logic may be updating stock twice:\n";
    echo "1. In the store() method when adding item\n";
    echo "2. In PembelianController when confirming purchase\n";
    
} else {
    echo "✅ Stock is correct!\n";
}

echo "\n=== CHECKING FOR SIMPLE PRODUCTS WITHOUT VARIANTS ===\n";
$simpleProductsInMovements = \App\Models\StockMovement::whereNull('variant_id')->count();
echo "Stock movements without variant_id: {$simpleProductsInMovements}\n";

if ($simpleProductsInMovements == 0) {
    echo "⚠️ No stock movements found for simple products!\n";
    echo "This suggests simple products are not being tracked in StockMovement table.\n";
    echo "Only variant products are being tracked.\n";
}