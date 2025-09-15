<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

$product = Product::where('name', 'like', '%Kertas A10%')->first();

if ($product) {
    echo "Produk ditemukan:\n";
    echo "ID: " . $product->id . "\n";
    echo "Nama: " . $product->name . "\n";
    echo "Smart Print Enabled: " . ($product->is_smart_print_enabled ? 'YES' : 'NO') . "\n";
    echo "Print Service: " . ($product->is_print_service ? 'YES' : 'NO') . "\n";
    echo "Status: " . $product->status . "\n";
} else {
    echo "Produk Kertas A10 tidak ditemukan\n";
    
    echo "\nMenampilkan semua produk dengan nama mengandung 'Kertas':\n";
    $products = Product::where('name', 'like', '%Kertas%')->get();
    foreach ($products as $p) {
        echo "- " . $p->name . " (Smart Print: " . ($p->is_smart_print_enabled ? 'YES' : 'NO') . ")\n";
    }
}