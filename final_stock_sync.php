<?php

require_once './vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\StockMovement;

$app = require_once './bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FINAL STOCK SYNCHRONIZATION ===\n\n";

// Step 1: Get all inconsistencies
echo "🔍 Mencari semua inconsistency stok...\n";

$inconsistencies = DB::select("
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
    WHERE pi.qty IS NOT NULL 
    AND pv.stock IS NOT NULL 
    AND pi.qty != pv.stock
    ORDER BY ABS(pi.qty - pv.stock) DESC
");

$totalInconsistencies = count($inconsistencies);
echo "Ditemukan {$totalInconsistencies} produk dengan inconsistency\n\n";

if ($totalInconsistencies === 0) {
    echo "✅ Semua stok sudah sinkron!\n";
    exit;
}

// Step 2: Show top inconsistencies
echo "📊 Top 5 produk dengan perbedaan terbesar:\n";
echo str_pad("ID", 6) . str_pad("Product Name", 30) . str_pad("Inventory", 12) . str_pad("Variant", 12) . str_pad("Diff", 8) . "\n";
echo str_repeat("=", 70) . "\n";

foreach (array_slice($inconsistencies, 0, 5) as $item) {
    echo str_pad($item->product_id, 6) . 
         str_pad(substr($item->product_name, 0, 29), 30) .
         str_pad($item->inventory_qty, 12) .
         str_pad($item->variant_stock, 12) .
         str_pad($item->difference, 8) . "\n";
}

echo "\n🛠️  Memulai sinkronisasi...\n\n";

$syncedCount = 0;
$errorCount = 0;

DB::beginTransaction();

try {
    foreach ($inconsistencies as $item) {
        echo "🔄 Sinkronisasi: {$item->product_name} (ID: {$item->product_id})\n";
        echo "   Current: Inventory={$item->inventory_qty}, Variant={$item->variant_stock}\n";
        
        // Tentukan stok yang benar berdasarkan StockMovement terbaru
        $latestMovement = DB::table('stock_movements')
            ->where('variant_id', $item->variant_id)
            ->orderBy('created_at', 'desc')
            ->first();
        
        $correctStock = null;
        $reason = '';
        
        if ($latestMovement) {
            $correctStock = $latestMovement->new_stock;
            $reason = "dari StockMovement terbaru";
        } else {
            // Gunakan nilai yang lebih tinggi
            $correctStock = max($item->inventory_qty, $item->variant_stock);
            $reason = "nilai tertinggi (Inventory vs Variant)";
        }
        
        echo "   Target: {$correctStock} ({$reason})\n";
        
        // Update ProductInventory
        $inventoryResult = DB::table('product_inventories')
            ->where('product_id', $item->product_id)
            ->update(['qty' => $correctStock]);
        
        // Update ProductVariant
        $variantResult = DB::table('product_variants')
            ->where('id', $item->variant_id)
            ->update(['stock' => $correctStock]);
        
        if ($inventoryResult !== false && $variantResult !== false) {
            // Record the synchronization in StockMovement
            if ($correctStock != $item->variant_stock) {
                $movementType = ($correctStock > $item->variant_stock) ? StockMovement::MOVEMENT_IN : StockMovement::MOVEMENT_OUT;
                $quantity = abs($correctStock - $item->variant_stock);
                
                DB::table('stock_movements')->insert([
                    'variant_id' => $item->variant_id,
                    'movement_type' => $movementType,
                    'quantity' => $quantity,
                    'old_stock' => $item->variant_stock,
                    'new_stock' => $correctStock,
                    'reference_type' => 'system',
                    'reference_id' => null,
                    'reason' => StockMovement::REASON_STOCK_SYNCHRONIZATION,
                    'notes' => "Auto-sync: Inventory({$item->inventory_qty}) + Variant({$item->variant_stock}) = Target({$correctStock})",
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            echo "   ✅ Berhasil disinkronkan ke {$correctStock}\n";
            $syncedCount++;
        } else {
            echo "   ❌ Gagal update database\n";
            $errorCount++;
        }
        
        echo "\n";
    }
    
    DB::commit();
    
    echo "🎉 SINKRONISASI SELESAI!\n";
    echo "✅ Berhasil: {$syncedCount} produk\n";
    echo "❌ Gagal: {$errorCount} produk\n\n";
    
    // Final verification
    echo "🔍 Verifikasi hasil...\n";
    
    $remainingCheck = DB::select("
        SELECT COUNT(*) as count
        FROM products p
        LEFT JOIN product_inventories pi ON p.id = pi.product_id
        LEFT JOIN product_variants pv ON p.id = pv.product_id
        WHERE pi.qty IS NOT NULL 
        AND pv.stock IS NOT NULL 
        AND pi.qty != pv.stock
    ");
    
    $remainingCount = $remainingCheck[0]->count;
    
    if ($remainingCount == 0) {
        echo "🎊 SEMPURNA! Semua {$totalInconsistencies} produk berhasil disinkronkan!\n";
        echo "🚀 Sistem stok sekarang 100% konsisten!\n";
    } else {
        echo "⚠️  Masih ada {$remainingCount} produk yang belum sinkron\n";
    }
    
    // Test AMPLOP specifically
    echo "\n🧪 Test AMPLOP setelah sinkronisasi:\n";
    $amplopCheck = DB::select("
        SELECT 
            p.name,
            pi.qty as inventory_qty,
            pv.stock as variant_stock
        FROM products p
        LEFT JOIN product_inventories pi ON p.id = pi.product_id
        LEFT JOIN product_variants pv ON p.id = pv.product_id
        WHERE p.name LIKE '%AMPLOP%'
        LIMIT 1
    ");
    
    if (!empty($amplopCheck)) {
        $amplop = $amplopCheck[0];
        echo "AMPLOP - Inventory: {$amplop->inventory_qty}, Variant: {$amplop->variant_stock}\n";
        
        if ($amplop->inventory_qty == $amplop->variant_stock) {
            echo "✅ AMPLOP berhasil sinkron!\n";
        } else {
            echo "❌ AMPLOP masih belum sinkron\n";
        }
    }
    
} catch (Exception $e) {
    DB::rollback();
    echo "💥 ERROR: " . $e->getMessage() . "\n";
    echo "❌ Transaksi dibatalkan!\n";
}

echo "\n=== SYNC COMPLETE ===\n";