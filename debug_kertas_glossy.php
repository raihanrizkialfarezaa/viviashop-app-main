<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StockManagementService;

echo "=== DEBUG KERTAS GLOSSY ===\n\n";

// Cari produk kertas glossy yang baru dibuat
$glossyProduct = Product::where('name', 'LIKE', '%glossy%')
                       ->orWhere('name', 'LIKE', '%Glossy%')
                       ->orderBy('id', 'desc')
                       ->first();

if (!$glossyProduct) {
    echo "✗ Produk kertas glossy tidak ditemukan\n";
    echo "Mencoba mencari produk terbaru yang dibuat...\n\n";
    
    $latestProducts = Product::orderBy('id', 'desc')->limit(5)->get();
    echo "5 Produk Terbaru:\n";
    foreach ($latestProducts as $product) {
        echo "- ID {$product->id}: {$product->name}\n";
    }
    exit;
}

echo "✓ Produk Ditemukan: {$glossyProduct->name} (ID: {$glossyProduct->id})\n\n";

echo "=== ANALISIS PRODUK ===\n";
echo "Basic Info:\n";
echo "- Name: {$glossyProduct->name}\n";
echo "- SKU: {$glossyProduct->sku}\n";
echo "- Type: {$glossyProduct->type}\n";
echo "- Status: {$glossyProduct->status}\n";
echo "- Created: {$glossyProduct->created_at}\n\n";

echo "Print Service Settings:\n";
echo "- is_print_service: " . ($glossyProduct->is_print_service ? 'true' : 'false') . " (raw: " . (int)$glossyProduct->is_print_service . ")\n";
echo "- is_smart_print_enabled: " . ($glossyProduct->is_smart_print_enabled ? 'true' : 'false') . " (raw: " . (int)$glossyProduct->is_smart_print_enabled . ")\n\n";

// Check variants
echo "=== ANALISIS VARIANTS ===\n";
$variants = ProductVariant::where('product_id', $glossyProduct->id)->get();
echo "Total variants: " . $variants->count() . "\n";

if ($variants->count() > 0) {
    foreach ($variants as $variant) {
        echo "- Variant ID {$variant->id}:\n";
        echo "  * Name: {$variant->name}\n";
        echo "  * SKU: {$variant->sku}\n";
        echo "  * is_active: " . ($variant->is_active ? 'true' : 'false') . "\n";
        echo "  * stock: {$variant->stock}\n";
        echo "  * print_type: {$variant->print_type}\n";
        echo "  * created: {$variant->created_at}\n\n";
    }
} else {
    echo "✗ TIDAK ADA VARIANTS!\n";
    echo "Ini adalah masalah utama. Produk print service harus memiliki variants.\n\n";
}

// Check dengan StockManagementService
echo "=== TEST DENGAN STOCKMANAGEMENTSERVICE ===\n";
$stockService = new StockManagementService();
$allVariants = $stockService->getVariantsByStock();

echo "Total variants di stock management: " . $allVariants->count() . "\n";

// Cek apakah variants produk ini ada di hasil
$glossyVariants = $allVariants->filter(function($variant) use ($glossyProduct) {
    return $variant->product && $variant->product->id == $glossyProduct->id;
});

echo "Variants produk ini di stock management: " . $glossyVariants->count() . "\n\n";

if ($glossyVariants->count() == 0) {
    echo "✗ PRODUK TIDAK MUNCUL DI STOCK MANAGEMENT\n\n";
    
    echo "=== DIAGNOSA MASALAH ===\n";
    
    // Check kriteria satu per satu
    echo "Kriteria yang harus dipenuhi:\n";
    
    echo "1. product.is_print_service = true: " . ($glossyProduct->is_print_service ? '✓' : '✗') . "\n";
    if (!$glossyProduct->is_print_service) {
        echo "   ❌ MASALAH: Checkbox 'Aktifkan sebagai produk layanan cetak' tidak tersimpan!\n";
    }
    
    echo "2. product.status = 1: " . ($glossyProduct->status == 1 ? '✓' : '✗') . "\n";
    if ($glossyProduct->status != 1) {
        echo "   ❌ MASALAH: Status produk tidak aktif!\n";
    }
    
    echo "3. variant.is_active = true: ";
    $activeVariants = ProductVariant::where('product_id', $glossyProduct->id)->where('is_active', true)->count();
    echo ($activeVariants > 0 ? "✓ ($activeVariants variants)" : "✗ (0 variants)") . "\n";
    
    if ($activeVariants == 0) {
        echo "   ❌ MASALAH: Tidak ada variants aktif!\n";
        
        if ($variants->count() == 0) {
            echo "   📝 SOLUSI: Variants belum dibuat otomatis saat save produk\n";
        } else {
            echo "   📝 SOLUSI: Variants ada tapi tidak aktif, perlu di-activate\n";
        }
    }
    
} else {
    echo "✓ PRODUK SUDAH MUNCUL DI STOCK MANAGEMENT!\n";
}

echo "\n=== REKOMENDASI SOLUSI ===\n";

if (!$glossyProduct->is_print_service || !$glossyProduct->is_smart_print_enabled) {
    echo "🔧 SOLUSI 1: Fix checkbox values\n";
    echo "   - Checkbox tidak tersimpan dengan benar\n";
    echo "   - Perlu update manual atau edit ulang produk\n\n";
}

if ($variants->count() == 0) {
    echo "🔧 SOLUSI 2: Buat variants manual\n";
    echo "   - Produk smart print harus memiliki variants BW dan Color\n";
    echo "   - Auto-create variants mungkin tidak jalan saat save\n\n";
}

if ($variants->count() > 0 && $activeVariants == 0) {
    echo "🔧 SOLUSI 3: Activate variants\n";
    echo "   - Variants sudah ada tapi tidak aktif\n";
    echo "   - Perlu set is_active = true\n\n";
}

echo "Pilih solusi yang akan dijalankan:\n";
echo "A. Update checkbox values\n";
echo "B. Buat variants otomatis\n";
echo "C. Activate variants yang ada\n";
echo "D. Lakukan semua solusi sekaligus\n\n";

echo "=== DEBUG SELESAI ===\n";