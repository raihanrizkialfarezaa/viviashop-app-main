<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "Mencari produk Kertas A10...\n";
$product = Product::where('name', 'like', '%Kertas A10%')->first();

if ($product) {
    echo "Produk ditemukan: " . $product->name . "\n";
    echo "Status sebelum update:\n";
    echo "- is_smart_print_enabled: " . ($product->is_smart_print_enabled ? 'YES' : 'NO') . "\n";
    echo "- is_print_service: " . ($product->is_print_service ? 'YES' : 'NO') . "\n";
    
    // Update kedua field
    $product->is_smart_print_enabled = true;
    $product->is_print_service = true;
    $product->save();
    
    echo "\nStatus setelah update:\n";
    echo "- is_smart_print_enabled: " . ($product->is_smart_print_enabled ? 'YES' : 'NO') . "\n";
    echo "- is_print_service: " . ($product->is_print_service ? 'YES' : 'NO') . "\n";
    echo "\nProduk berhasil diupdate!\n";
} else {
    echo "Produk tidak ditemukan\n";
}