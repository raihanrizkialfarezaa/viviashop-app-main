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

echo "=== COMPREHENSIVE STOCK SYNCHRONIZATION ===\n\n";

// Langkah 1: Analisis masalah stok
echo "🔍 Menganalisis inconsistency stok...\n";

$inconsistencies = DB::select("
    SELECT 
        p.id as product_id,
        p.name as product_name,
        p.sku as product_sku,
        pi.qty as inventory_qty,
        pv.stock as variant_stock,
        (pi.qty - pv.stock) as difference,
        p.type as product_type
    FROM products p
    LEFT JOIN product_inventories pi ON p.id = pi.product_id
    LEFT JOIN product_variants pv ON p.id = pv.product_id
    WHERE pi.qty IS NOT NULL 
    AND pv.stock IS NOT NULL 
    AND pi.qty != pv.stock
    ORDER BY ABS(pi.qty - pv.stock) DESC
");

echo "Ditemukan " . count($inconsistencies) . " produk dengan inconsistency stok\n\n";

if (count($inconsistencies) === 0) {
    echo "✅ Semua stok sudah sinkron!\n";
    exit;
}

// Tampilkan 10 teratas dengan perbedaan terbesar
echo "📊 Top 10 produk dengan perbedaan stok terbesar:\n";
echo str_pad("ID", 5) . str_pad("SKU", 15) . str_pad("Nama", 25) . str_pad("Inventory", 12) . str_pad("Variant", 12) . str_pad("Diff", 8) . "\n";
echo str_repeat("=", 80) . "\n";

foreach (array_slice($inconsistencies, 0, 10) as $item) {
    echo str_pad($item->product_id, 5) . 
         str_pad(substr($item->product_sku ?? 'N/A', 0, 14), 15) .
         str_pad(substr($item->product_name, 0, 24), 25) .
         str_pad($item->inventory_qty, 12) .
         str_pad($item->variant_stock, 12) .
         str_pad($item->difference, 8) . "\n";
}

echo "\n🛠️  Memulai sinkronisasi stok...\n\n";

$syncedCount = 0;
$errorCount = 0;

// Langkah 2: Tentukan aturan sinkronisasi
echo "📋 Aturan Sinkronisasi:\n";
echo "1. Jika ada StockMovement terbaru, gunakan nilai tersebut\n";
echo "2. Jika tidak ada movement, gunakan nilai yang lebih tinggi (asumsi lebih akurat)\n";
echo "3. Update ProductInventory dan ProductVariant secara bersamaan\n";
echo "4. Catat semua perubahan dalam StockMovement\n\n";

DB::beginTransaction();

try {
    foreach ($inconsistencies as $item) {
        echo "🔄 Mensinkronkan produk {$item->product_id} ({$item->product_name})...\n";
        
        // Cari StockMovement terbaru untuk produk ini
        $latestMovement = DB::table('stock_movements')
            ->join('product_variants', 'stock_movements.variant_id', '=', 'product_variants.id')
            ->where('product_variants.product_id', $item->product_id)
            ->orderBy('stock_movements.created_at', 'desc')
            ->first();
        
        $correctStock = null;
        $reason = '';
        
        if ($latestMovement) {
            // Gunakan nilai dari movement terbaru
            $correctStock = $latestMovement->new_stock;
            $reason = "Berdasarkan StockMovement terbaru (ID: {$latestMovement->id})";
        } else {
            // Gunakan nilai yang lebih tinggi (asumsi lebih akurat)
            $correctStock = max($item->inventory_qty, $item->variant_stock);
            $reason = "Menggunakan nilai tertinggi (Inventory: {$item->inventory_qty}, Variant: {$item->variant_stock})";
        }
        
        echo "   📍 Stok yang benar: {$correctStock} - {$reason}\n";
        
        // Update ProductInventory
        $inventoryUpdated = DB::table('product_inventories')
            ->where('product_id', $item->product_id)
            ->update(['qty' => $correctStock]);
        
        // Update ProductVariant
        $variantUpdated = DB::table('product_variants')
            ->where('product_id', $item->product_id)
            ->update(['stock' => $correctStock]);
        
        // Catat perubahan dalam StockMovement jika ada perubahan signifikan
        if (abs($item->inventory_qty - $correctStock) > 0 || abs($item->variant_stock - $correctStock) > 0) {
            $variant = DB::table('product_variants')
                ->where('product_id', $item->product_id)
                ->first();
            
            if ($variant) {
                // Tentukan movement type berdasarkan perubahan
                $movementType = ($correctStock > $item->variant_stock) ? 'in' : 'out';
                $quantity = abs($correctStock - $item->variant_stock);
                
                if ($quantity > 0) {
                    DB::table('stock_movements')->insert([
                        'variant_id' => $variant->id,
                        'movement_type' => $movementType,
                        'quantity' => $quantity,
                        'old_stock' => $item->variant_stock,
                        'new_stock' => $correctStock,
                        'reference_type' => 'system',
                        'reference_id' => null,
                        'reason' => 'stock_synchronization',
                        'notes' => "Automatic sync: Inventory({$item->inventory_qty}) -> Variant({$item->variant_stock}) -> Correct({$correctStock})",
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
        
        if ($inventoryUpdated && $variantUpdated) {
            echo "   ✅ Berhasil disinkronkan\n";
            $syncedCount++;
        } else {
            echo "   ❌ Gagal sinkronisasi\n";
            $errorCount++;
        }
        
        echo "\n";
    }
    
    DB::commit();
    
    echo "🎉 SINKRONISASI SELESAI!\n";
    echo "✅ Berhasil: {$syncedCount} produk\n";
    echo "❌ Gagal: {$errorCount} produk\n\n";
    
    // Verifikasi hasil
    echo "🔍 Verifikasi hasil sinkronisasi...\n";
    
    $remainingInconsistencies = DB::select("
        SELECT COUNT(*) as count
        FROM products p
        LEFT JOIN product_inventories pi ON p.id = pi.product_id
        LEFT JOIN product_variants pv ON p.id = pv.product_id
        WHERE pi.qty IS NOT NULL 
        AND pv.stock IS NOT NULL 
        AND pi.qty != pv.stock
    ");
    
    $remaining = $remainingInconsistencies[0]->count;
    
    if ($remaining == 0) {
        echo "🎊 SEMPURNA! Semua stok sudah sinkron!\n";
    } else {
        echo "⚠️  Masih ada {$remaining} produk yang belum sinkron. Perlu investigasi lebih lanjut.\n";
    }
    
} catch (Exception $e) {
    DB::rollback();
    echo "💥 ERROR: " . $e->getMessage() . "\n";
    echo "❌ Sinkronisasi dibatalkan!\n";
}

echo "\n=== SELESAI ===\n";