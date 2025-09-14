<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== SINKRONISASI STOK KOMPREHENSIF ===\n";

$amplop = \App\Models\Product::with(['productInventory', 'productVariants'])->find(9);

echo "\n1. KEADAAN SEBELUM SINKRONISASI\n";
echo "ProductInventory qty: " . ($amplop->productInventory ? $amplop->productInventory->qty : 'null') . "\n";
echo "ProductVariant stock: " . ($amplop->productVariants->first() ? $amplop->productVariants->first()->stock : 'null') . "\n";

echo "\n2. MENENTUKAN STOK YANG BENAR BERDASARKAN STOCK MOVEMENTS\n";
$stockMovements = \App\Models\StockMovement::whereHas('variant', function($q) {
    $q->where('product_id', 9);
})->orderBy('created_at', 'asc')->get();

$correctStock = 0;
echo "Calculating stock from movements:\n";
foreach ($stockMovements as $movement) {
    if ($movement->movement_type === 'in') {
        $correctStock += $movement->quantity;
        echo "+ {$movement->quantity} (IN) = {$correctStock}\n";
    } else {
        $correctStock -= $movement->quantity;
        echo "- {$movement->quantity} (OUT) = {$correctStock}\n";
    }
}

echo "\nStock yang benar berdasarkan movements: {$correctStock}\n";

echo "\n3. MELIHAT STOCK MOVEMENT TERBARU\n";
$latestMovement = \App\Models\StockMovement::whereHas('variant', function($q) {
    $q->where('product_id', 9);
})->orderBy('created_at', 'desc')->first();

if ($latestMovement) {
    echo "Latest movement new_stock: {$latestMovement->new_stock}\n";
    $correctStock = $latestMovement->new_stock;
} else {
    echo "No movements found, using base calculation\n";
}

echo "\n4. SINKRONISASI SEMUA TABEL\n";

echo "Updating ProductInventory...\n";
if ($amplop->productInventory) {
    $amplop->productInventory->qty = $correctStock;
    $amplop->productInventory->save();
    echo "✅ ProductInventory updated to: {$correctStock}\n";
} else {
    \App\Models\ProductInventory::create([
        'product_id' => 9,
        'qty' => $correctStock
    ]);
    echo "✅ ProductInventory created with: {$correctStock}\n";
}

echo "Updating ProductVariant...\n";
$variant = $amplop->productVariants->first();
if ($variant) {
    $variant->stock = $correctStock;
    $variant->save();
    echo "✅ ProductVariant updated to: {$correctStock}\n";
} else {
    echo "❌ No variant found to update\n";
}

echo "\n5. VERIFIKASI SETELAH SINKRONISASI\n";
$amplop->refresh();
echo "ProductInventory qty: " . ($amplop->productInventory ? $amplop->productInventory->qty : 'null') . "\n";
echo "ProductVariant stock: " . ($amplop->productVariants->first() ? $amplop->productVariants->first()->stock : 'null') . "\n";

echo "\n6. MEMBUAT FUNGSI SINKRONISASI OTOMATIS\n";
echo "Creating synchronization function for all products...\n";

$allProducts = \App\Models\Product::with(['productInventory', 'productVariants'])->get();
echo "Checking {$allProducts->count()} products for inconsistencies...\n";

$inconsistentProducts = [];
foreach ($allProducts as $product) {
    $inventoryQty = $product->productInventory ? $product->productInventory->qty : 0;
    $variantStock = $product->productVariants->sum('stock');
    
    if ($inventoryQty != $variantStock) {
        $inconsistentProducts[] = [
            'id' => $product->id,
            'name' => $product->name,
            'inventory_qty' => $inventoryQty,
            'variant_stock' => $variantStock
        ];
    }
}

echo "Found " . count($inconsistentProducts) . " inconsistent products:\n";
foreach ($inconsistentProducts as $prod) {
    echo "- Product {$prod['id']} ({$prod['name']}): Inventory={$prod['inventory_qty']}, Variant={$prod['variant_stock']}\n";
}

echo "\n=== SINKRONISASI SELESAI ===\n";