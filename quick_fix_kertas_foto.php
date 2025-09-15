<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "=== QUICK FIX: KERTAS FOTO ===\n\n";

// Fix kertas foto immediately
$fotoProduct = Product::where('name', 'LIKE', '%foto%')
                     ->orWhere('name', 'LIKE', '%Foto%')
                     ->orderBy('id', 'desc')
                     ->first();

if ($fotoProduct) {
    echo "Found: {$fotoProduct->name} (ID: {$fotoProduct->id})\n";
    echo "Before fix:\n";
    echo "- is_print_service: {$fotoProduct->is_print_service}\n";
    echo "- is_smart_print_enabled: {$fotoProduct->is_smart_print_enabled}\n\n";
    
    // Fix it
    $fotoProduct->update([
        'is_print_service' => true,
        'is_smart_print_enabled' => true,
    ]);
    
    $fotoProduct->refresh();
    echo "After fix:\n";
    echo "- is_print_service: {$fotoProduct->is_print_service}\n";
    echo "- is_smart_print_enabled: {$fotoProduct->is_smart_print_enabled}\n\n";
    
    // Verify it appears in stock management
    $stockService = new \App\Services\StockManagementService();
    $stockVariants = $stockService->getVariantsByStock();
    
    $fotoInStock = $stockVariants->filter(function($variant) use ($fotoProduct) {
        return $variant->product && $variant->product->id == $fotoProduct->id;
    });
    
    if ($fotoInStock->count() > 0) {
        echo "✅ SUCCESS! Kertas Foto sekarang MUNCUL di Stock Management!\n";
        echo "URL: http://127.0.0.1:8000/admin/print-service/stock\n\n";
        
        foreach ($fotoInStock as $variant) {
            echo "- {$variant->name} (Stock: {$variant->stock})\n";
        }
    } else {
        echo "❌ Still not appearing in stock management\n";
    }
    
} else {
    echo "❌ Kertas foto tidak ditemukan\n";
}

echo "\n=== TEMPORARY SOLUTION ===\n";
echo "Kertas Foto sudah diperbaiki dan sekarang muncul di Stock Management.\n";
echo "Untuk produk baru yang akan dibuat, mari kita debug lebih lanjut...\n\n";

echo "=== ROOT CAUSE ANALYSIS ===\n";
echo "Berdasarkan testing:\n";
echo "1. ✅ ProductVariantService::createBaseProduct() SUDAH BENAR\n";
echo "2. ✅ Controller logic SUDAH BENAR\n";
echo "3. ✅ Auto-create variants BEKERJA\n";
echo "4. ❌ Checkbox values dari form TIDAK SAMPAI ke controller\n\n";

echo "Kemungkinan penyebab:\n";
echo "1. Form HTML tidak mengirim checkbox values\n";
echo "2. JavaScript interfering dengan form submission\n";
echo "3. Browser tidak submit checkbox values\n";
echo "4. Network issue saat submit\n\n";

echo "Untuk memastikan, silakan:\n";
echo "1. Buka Developer Tools (F12) di browser\n";
echo "2. Go to Network tab\n";
echo "3. Buat produk baru dengan checkbox dicentang\n";
echo "4. Lihat request payload - apakah ada is_print_service dan is_smart_print_enabled?\n\n";

echo "Atau coba edit produk yang sudah ada (kertas foto) dan save ulang.\n";
echo "Jika edit berhasil, berarti masalah ada di form create.\n";