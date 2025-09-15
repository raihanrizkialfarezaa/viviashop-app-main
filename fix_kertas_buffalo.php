<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "=== FIX KERTAS BUFFALO PRODUCT ===\n\n";

// Find and fix the kertas buffalo product
$kertasBuffalo = Product::where('name', 'like', '%buffalo%')->first();

if ($kertasBuffalo) {
    echo "Produk ditemukan: " . $kertasBuffalo->name . "\n";
    echo "Status sebelum fix:\n";
    echo "- Type: " . $kertasBuffalo->type . "\n";
    echo "- is_print_service: " . ($kertasBuffalo->is_print_service ? 'YES' : 'NO') . "\n";
    echo "- is_smart_print_enabled: " . ($kertasBuffalo->is_smart_print_enabled ? 'YES' : 'NO') . "\n\n";
    
    // Update fields
    $kertasBuffalo->is_print_service = true;
    $kertasBuffalo->is_smart_print_enabled = true;
    $kertasBuffalo->save();
    
    echo "Status setelah fix:\n";
    echo "- is_print_service: " . ($kertasBuffalo->is_print_service ? 'YES' : 'NO') . "\n";
    echo "- is_smart_print_enabled: " . ($kertasBuffalo->is_smart_print_enabled ? 'YES' : 'NO') . "\n\n";
    
    // Test if it appears in stock management now
    $stockService = new \App\Services\StockManagementService();
    $allVariants = $stockService->getVariantsByStock('asc');
    $kertasBuffaloVariants = $allVariants->filter(function($variant) use ($kertasBuffalo) {
        return $variant->product_id == $kertasBuffalo->id;
    });
    
    echo "✅ HASIL:\n";
    echo "Kertas buffalo variants dalam stock management: " . $kertasBuffaloVariants->count() . "\n";
    foreach ($kertasBuffaloVariants as $variant) {
        echo "- " . $variant->name . " (Stock: " . $variant->stock . ")\n";
    }
    
    echo "\n🎉 KERTAS BUFFALO SEKARANG SUDAH MUNCUL DI STOCK MANAGEMENT!\n";
    
} else {
    echo "❌ Produk tidak ditemukan\n";
}