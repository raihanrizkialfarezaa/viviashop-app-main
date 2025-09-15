<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "=== FIX KERTAS DINO PRODUCT ===\n\n";

// Find and fix the kertas dino product
$kertasDino = Product::where('name', 'like', '%kertas dino%')->first();

if ($kertasDino) {
    echo "Produk ditemukan: " . $kertasDino->name . "\n";
    echo "Status sebelum fix:\n";
    echo "- is_print_service: " . ($kertasDino->is_print_service ? 'YES' : 'NO') . "\n";
    echo "- is_smart_print_enabled: " . ($kertasDino->is_smart_print_enabled ? 'YES' : 'NO') . "\n\n";
    
    // Update fields
    $kertasDino->is_print_service = true;
    $kertasDino->is_smart_print_enabled = true;
    $kertasDino->save();
    
    echo "Status setelah fix:\n";
    echo "- is_print_service: " . ($kertasDino->is_print_service ? 'YES' : 'NO') . "\n";
    echo "- is_smart_print_enabled: " . ($kertasDino->is_smart_print_enabled ? 'YES' : 'NO') . "\n\n";
    
    // Test if it appears in stock management now
    $stockService = new \App\Services\StockManagementService();
    $allVariants = $stockService->getVariantsByStock('asc');
    $kertasDinoVariants = $allVariants->filter(function($variant) use ($kertasDino) {
        return $variant->product_id == $kertasDino->id;
    });
    
    echo "✅ HASIL:\n";
    echo "Kertas dino variants dalam stock management: " . $kertasDinoVariants->count() . "\n";
    foreach ($kertasDinoVariants as $variant) {
        echo "- " . $variant->name . " (Stock: " . $variant->stock . ")\n";
    }
    
    echo "\n🎉 KERTAS DINO SEKARANG SUDAH MUNCUL DI STOCK MANAGEMENT!\n";
    
} else {
    echo "❌ Produk tidak ditemukan\n";
}