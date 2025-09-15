<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\StockManagementService;
use App\Models\Product;
use App\Models\ProductVariant;

echo "=== VERIFIKASI PRODUK TEST DI STOCK MANAGEMENT ===\n\n";

// Cari produk test yang baru dibuat (yang terbaru)
$testProduct = Product::where('name', 'LIKE', '%Test Kertas Checkbox Fix%')
                     ->orderBy('id', 'desc')
                     ->first();

if (!$testProduct) {
    echo "✗ Produk test tidak ditemukan\n";
    exit;
}

echo "Produk Test Ditemukan:\n";
echo "- ID: {$testProduct->id}\n";
echo "- Name: {$testProduct->name}\n";
echo "- is_print_service: " . ($testProduct->is_print_service ? 'true' : 'false') . "\n";
echo "- is_smart_print_enabled: " . ($testProduct->is_smart_print_enabled ? 'true' : 'false') . "\n";
echo "- status: {$testProduct->status}\n";

$variantCount = ProductVariant::where('product_id', $testProduct->id)->where('is_active', true)->count();
echo "- active variants: {$variantCount}\n\n";

// Test dengan StockManagementService
echo "=== TEST DENGAN STOCKMANAGEMENTSERVICE ===\n";
$stockService = new StockManagementService();
$stockVariants = $stockService->getVariantsByStock();

echo "Total variants di stock management: " . $stockVariants->count() . "\n\n";

// Cek apakah variants dari produk test ada di hasil
$testVariants = $stockVariants->filter(function($variant) use ($testProduct) {
    return $variant->product && $variant->product->id == $testProduct->id;
});

if ($testVariants->count() > 0) {
    echo "✓ PRODUK TEST MUNCUL DI STOCK MANAGEMENT!\n";
    echo "- Product ID: {$testProduct->id}\n";
    echo "- Product Name: {$testProduct->name}\n";
    echo "- Variants found: " . $testVariants->count() . "\n";
    
    foreach ($testVariants as $variant) {
        echo "  * Variant ID {$variant->id}: {$variant->name} (stock: {$variant->stock})\n";
    }
} else {
    echo "✗ PRODUK TEST TIDAK MUNCUL DI STOCK MANAGEMENT\n";
    
    // Debug mengapa tidak muncul
    echo "\nDEBUG INFO:\n";
    echo "Filter criteria untuk variants:\n";
    echo "- product.is_print_service = 1: " . ($testProduct->is_print_service == 1 ? '✓' : '✗') . "\n";
    echo "- product.status = 1: " . ($testProduct->status == 1 ? '✓' : '✗') . "\n";
    echo "- variant.is_active = true: ";
    
    // Check variant aktif
    $testProductVariants = ProductVariant::where('product_id', $testProduct->id)->where('is_active', true)->get();
    echo ($testProductVariants->count() > 0 ? '✓' : '✗') . " ({$testProductVariants->count()} active variants)\n";
    
    if ($testProductVariants->count() == 0) {
        // Check semua variants
        $allVariants = ProductVariant::where('product_id', $testProduct->id)->get();
        echo "  Total variants (all): {$allVariants->count()}\n";
        foreach ($allVariants as $v) {
            echo "    - Variant ID {$v->id}: is_active={$v->is_active}, name={$v->name}\n";
        }
    }
}

echo "\n=== HASIL VERIFIKASI ===\n";
if ($testVariants->count() > 0) {
    echo "✓ CHECKBOX FIX BERHASIL! Produk dengan checkbox yang diceklis sekarang muncul di stock management.\n";
} else {
    echo "✗ Masih ada masalah dengan checkbox handling atau variant setup.\n";
}

echo "\n=== CLEANUP ===\n";
// Hapus produk test dan variants
if ($testProduct) {
    // Hapus variants dulu
    ProductVariant::where('product_id', $testProduct->id)->delete();
    echo "✓ Variants test dihapus\n";
    
    // Hapus produk
    $testProduct->delete();
    echo "✓ Produk test dihapus\n";
}

echo "\n=== VERIFIKASI SELESAI ===\n";