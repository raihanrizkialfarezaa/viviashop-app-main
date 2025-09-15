<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

echo "=== FIX KERTAS GLOSSY - SOLUSI LANGSUNG ===\n\n";

// Ambil produk kertas glossy
$glossyProduct = Product::where('name', 'LIKE', '%glossy%')
                       ->orWhere('name', 'LIKE', '%Glossy%')
                       ->orderBy('id', 'desc')
                       ->first();

if (!$glossyProduct) {
    echo "✗ Produk tidak ditemukan\n";
    exit;
}

echo "Produk: {$glossyProduct->name} (ID: {$glossyProduct->id})\n";
echo "Status sebelum fix:\n";
echo "- is_print_service: " . ($glossyProduct->is_print_service ? 'true' : 'false') . "\n";
echo "- is_smart_print_enabled: " . ($glossyProduct->is_smart_print_enabled ? 'true' : 'false') . "\n\n";

// Fix checkbox values
echo "=== FIXING CHECKBOX VALUES ===\n";
$glossyProduct->update([
    'is_print_service' => true,
    'is_smart_print_enabled' => true,
]);

$glossyProduct->refresh();
echo "✓ Checkbox values berhasil diupdate!\n";
echo "- is_print_service: " . ($glossyProduct->is_print_service ? 'true' : 'false') . "\n";
echo "- is_smart_print_enabled: " . ($glossyProduct->is_smart_print_enabled ? 'true' : 'false') . "\n\n";

// Verifikasi variants
echo "=== VERIFIKASI VARIANTS ===\n";
$variants = ProductVariant::where('product_id', $glossyProduct->id)->where('is_active', true)->get();
echo "Active variants: " . $variants->count() . "\n";

foreach ($variants as $variant) {
    echo "- {$variant->name} (ID: {$variant->id})\n";
}

// Test dengan StockManagementService
echo "\n=== VERIFIKASI STOCK MANAGEMENT ===\n";
$stockService = new \App\Services\StockManagementService();
$stockVariants = $stockService->getVariantsByStock();

$glossyInStock = $stockVariants->filter(function($variant) use ($glossyProduct) {
    return $variant->product && $variant->product->id == $glossyProduct->id;
});

if ($glossyInStock->count() > 0) {
    echo "✅ SUCCESS! Kertas Glossy sekarang MUNCUL di Stock Management!\n";
    echo "Variants yang muncul:\n";
    foreach ($glossyInStock as $variant) {
        echo "- {$variant->name} (Stock: {$variant->stock})\n";
    }
} else {
    echo "❌ Masih belum muncul di Stock Management\n";
}

echo "\n🎉 SOLUSI SELESAI!\n";
echo "Silakan cek halaman Stock Management: http://127.0.0.1:8000/admin/print-service/stock\n";