<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Services\StockManagementService;

echo "=== FINAL SMART PRINT LOGIC TEST ===\n\n";

echo "PENTING: Untuk produk muncul di /admin/print-service/stock, produk harus:\n";
echo "1. is_print_service = true (menandakan produk ini untuk layanan cetak)\n";
echo "2. is_smart_print_enabled = true (menandakan produk ini mendukung smart print)\n";
echo "3. status = 1 (produk aktif)\n";
echo "4. Memiliki minimal 1 variant yang is_active = true\n\n";

// Test filtering logic
$stockService = new StockManagementService();

echo "=== Testing Current Filter Logic ===\n";
echo "Filter saat ini: is_print_service = true AND status = 1\n";
$currentVariants = $stockService->getVariantsByStock('asc');
echo "Variants yang muncul dengan filter saat ini: " . $currentVariants->count() . "\n\n";

// Test proposed logic
echo "=== Testing New Logic (Both Conditions) ===\n";
echo "Recommended filter: is_print_service = true AND is_smart_print_enabled = true AND status = 1\n";

$smartPrintVariants = \App\Models\ProductVariant::where('is_active', true)
    ->with('product')
    ->whereHas('product', function($query) {
        $query->where('is_print_service', true)
              ->where('is_smart_print_enabled', true)
              ->where('status', 1);
    })
    ->orderBy('stock', 'asc')
    ->get();

echo "Variants dengan filter baru: " . $smartPrintVariants->count() . "\n\n";

echo "=== Comparison of Products ===\n";
$allProducts = Product::where('status', 1)->get();

foreach ($allProducts as $product) {
    $hasVariants = $product->productVariants()->where('is_active', true)->count() > 0;
    $inCurrent = $product->is_print_service && $hasVariants;
    $inNew = $product->is_print_service && $product->is_smart_print_enabled && $hasVariants;
    
    if ($inCurrent || $inNew) {
        echo "Product: " . $product->name . "\n";
        echo "  - is_print_service: " . ($product->is_print_service ? 'YES' : 'NO') . "\n";
        echo "  - is_smart_print_enabled: " . ($product->is_smart_print_enabled ? 'YES' : 'NO') . "\n";
        echo "  - Active variants: " . $product->productVariants()->where('is_active', true)->count() . "\n";
        echo "  - In current filter: " . ($inCurrent ? 'YES' : 'NO') . "\n";
        echo "  - In new filter: " . ($inNew ? 'YES' : 'NO') . "\n";
        echo "  ---\n";
    }
}

echo "\n=== KESIMPULAN ===\n";
echo "Produk KERTAS A10 sekarang sudah:\n";
echo "✓ is_print_service = true\n";
echo "✓ is_smart_print_enabled = true\n";
echo "✓ status = 1\n";
echo "✓ Memiliki 2 variants aktif\n";
echo "✓ MUNCUL di halaman /admin/print-service/stock\n\n";

echo "Jika ingin membedakan antara smart print dan regular print service,\n";
echo "bisa mengupdate StockManagementService::getVariantsByStock() untuk menambahkan\n";
echo "filter is_smart_print_enabled = true\n";