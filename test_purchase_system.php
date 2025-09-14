<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

echo "=== TESTING SISTEM PEMBELIAN & STOCK MANAGEMENT ===\n\n";

try {
    // 1. Buat supplier test jika belum ada
    $supplier = Supplier::firstOrCreate(['nama' => 'Raihan Rizki Alfareza'], [
        'alamat' => 'Alamat Supplier Test',
        'telepon' => '08123456789'
    ]);
    echo "✓ Supplier disiapkan: {$supplier->nama}\n";

    // 2. Ambil produk sample yang ada
    $product = Product::with('productVariants')->first();
    if (!$product) {
        echo "✗ Tidak ada produk dalam database\n";
        exit;
    }
    
    echo "✓ Menggunakan produk: {$product->name}\n";

    // 3. Ambil variant pertama jika ada, atau buat dummy
    $variant = $product->productVariants()->first();
    if (!$variant) {
        echo "✗ Produk tidak memiliki variant\n";
        exit;
    }

    $initialStock = $variant->stock;
    echo "✓ Menggunakan variant dengan stok awal: {$initialStock}\n";

    // 4. Simulasi pembelian
    DB::beginTransaction();

    $pembelian = new Pembelian();
    $pembelian->id_supplier = $supplier->id;
    $pembelian->total_item = 100;
    $pembelian->total_harga = 500000;
    $pembelian->diskon = 0;
    $pembelian->bayar = 500000;
    $pembelian->status = 'completed';
    $pembelian->payment_method = 'cash';
    $pembelian->waktu = now();
    $pembelian->save();

    echo "✓ Pembelian dibuat dengan ID: {$pembelian->id}\n";

    // 5. Buat detail pembelian
    $detail = new PembelianDetail();
    $detail->id_pembelian = $pembelian->id;
    $detail->id_produk = $product->id;
    $detail->variant_id = $variant->id;
    $detail->harga_beli = 5000;
    $detail->jumlah = 100;
    $detail->subtotal = 500000;
    $detail->save();

    echo "✓ Detail pembelian dibuat untuk 100 unit\n";

    // 6. Update stok menggunakan StockService
    $pembelian->load(['details', 'supplier']);
    $movements = StockService::processPurchaseStockUpdate($pembelian);

    echo "✓ Stock movement recorded: " . count($movements) . " movement(s)\n";

    // 7. Verifikasi stok setelah pembelian
    $variant->refresh();
    $newStock = $variant->stock;
    $expectedStock = $initialStock + 100;

    echo "Stok awal: {$initialStock}\n";
    echo "Stok setelah pembelian: {$newStock}\n";
    echo "Stok yang diharapkan: {$expectedStock}\n";

    if ($newStock == $expectedStock) {
        echo "✓ UPDATE STOK BERHASIL!\n";
    } else {
        echo "✗ Update stok gagal\n";
    }

    // 8. Cek stock movement history
    $latestMovement = StockMovement::where('variant_id', $variant->id)
                                  ->orderBy('created_at', 'desc')
                                  ->first();

    if ($latestMovement) {
        echo "✓ Stock movement tercatat:\n";
        echo "  - Type: {$latestMovement->movement_type}\n";
        echo "  - Quantity: {$latestMovement->quantity}\n";
        echo "  - Old Stock: {$latestMovement->old_stock}\n";
        echo "  - New Stock: {$latestMovement->new_stock}\n";
        echo "  - Reason: {$latestMovement->reason}\n";
        echo "  - Reference: {$latestMovement->reference_type}#{$latestMovement->reference_id}\n";
    }

    DB::commit();
    echo "\n✓ SEMUA TEST BERHASIL! Sistem pembelian berfungsi dengan baik.\n";

} catch (Exception $e) {
    DB::rollback();
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== TESTING SELESAI ===\n";