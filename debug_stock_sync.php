<?php

require_once './vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\ProductVariant;
use App\Models\StockMovement;

$app = require_once './bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEBUG STOCK SYNC ISSUE ===\n\n";

// Debug AMPLOP specifically
echo "🔍 Debug AMPLOP Product:\n";

$amplop = DB::select("
    SELECT 
        p.id as product_id,
        p.name as product_name,
        p.sku as product_sku,
        pi.qty as inventory_qty,
        pv.id as variant_id,
        pv.stock as variant_stock,
        (pi.qty - pv.stock) as difference
    FROM products p
    LEFT JOIN product_inventories pi ON p.id = pi.product_id
    LEFT JOIN product_variants pv ON p.id = pv.product_id
    WHERE p.name LIKE '%AMPLOP%'
    LIMIT 5
");

foreach ($amplop as $item) {
    echo "Product ID: {$item->product_id}\n";
    echo "Name: {$item->product_name}\n";
    echo "SKU: {$item->product_sku}\n";
    echo "Inventory Qty: {$item->inventory_qty}\n";
    echo "Variant ID: {$item->variant_id}\n";
    echo "Variant Stock: {$item->variant_stock}\n";
    echo "Difference: {$item->difference}\n";
    echo "---\n";
}

// Test simple update
echo "\n🧪 Testing Simple Update:\n";

try {
    // Update inventory first
    $inventoryResult = DB::table('product_inventories')
        ->where('product_id', 114)
        ->update(['qty' => 50]);
    
    echo "Inventory update result: " . ($inventoryResult ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Update variant
    $variantResult = DB::table('product_variants')
        ->where('product_id', 114)
        ->update(['stock' => 50]);
    
    echo "Variant update result: " . ($variantResult ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Check result
    $checkResult = DB::select("
        SELECT 
            pi.qty as inventory_qty,
            pv.stock as variant_stock
        FROM products p
        LEFT JOIN product_inventories pi ON p.id = pi.product_id
        LEFT JOIN product_variants pv ON p.id = pv.product_id
        WHERE p.id = 114
        LIMIT 1
    ");
    
    if (!empty($checkResult)) {
        $result = $checkResult[0];
        echo "After update - Inventory: {$result->inventory_qty}, Variant: {$result->variant_stock}\n";
        
        if ($result->inventory_qty == 50 && $result->variant_stock == 50) {
            echo "✅ AMPLOP berhasil disinkronkan!\n";
        } else {
            echo "❌ Masih belum sinkron\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Check all remaining inconsistencies
echo "\n🔍 Checking remaining inconsistencies:\n";

$remaining = DB::select("
    SELECT 
        p.id as product_id,
        p.name as product_name,
        pi.qty as inventory_qty,
        pv.stock as variant_stock,
        (pi.qty - pv.stock) as difference
    FROM products p
    LEFT JOIN product_inventories pi ON p.id = pi.product_id
    LEFT JOIN product_variants pv ON p.id = pv.product_id
    WHERE pi.qty IS NOT NULL 
    AND pv.stock IS NOT NULL 
    AND pi.qty != pv.stock
    ORDER BY ABS(pi.qty - pv.stock) DESC
    LIMIT 10
");

echo "Found " . count($remaining) . " remaining inconsistencies:\n";

foreach ($remaining as $item) {
    echo "ID {$item->product_id}: {$item->product_name} - Inventory: {$item->inventory_qty}, Variant: {$item->variant_stock}, Diff: {$item->difference}\n";
}

echo "\n=== DEBUG SELESAI ===\n";