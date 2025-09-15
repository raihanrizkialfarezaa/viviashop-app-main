<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

echo "Membuat variant untuk KERTAS A10...\n";
$product = Product::where('name', 'like', '%Kertas A10%')->first();

if ($product) {
    echo "Produk ID: " . $product->id . "\n";
    
    // Membuat variant dengan format yang benar
    $variant = new ProductVariant();
    $variant->product_id = $product->id;
    $variant->sku = 'A10-STD-001';
    $variant->name = 'A10 Standard';
    $variant->price = $product->price;  // Menggunakan harga dari produk
    $variant->harga_beli = $product->harga_beli;  // Menggunakan harga beli dari produk
    $variant->paper_size = 'A4';  // Menggunakan format yang valid
    $variant->print_type = 'bw';  // Menggunakan format yang valid
    $variant->stock = 100;
    $variant->min_stock_threshold = 10;
    $variant->is_active = true;
    $variant->weight = $product->weight;
    $variant->length = $product->length;
    $variant->width = $product->width;
    $variant->height = $product->height;
    $variant->save();
    
    echo "Variant berhasil dibuat!\n";
    echo "- Variant ID: " . $variant->id . "\n";
    echo "- Name: " . $variant->name . "\n";
    echo "- Paper Size: " . $variant->paper_size . "\n";
    echo "- Print Type: " . $variant->print_type . "\n";
    echo "- Stock: " . $variant->stock . "\n";
    echo "- Is Active: " . ($variant->is_active ? 'YES' : 'NO') . "\n";
    
    // Mari buat juga variant untuk color print
    $variantColor = new ProductVariant();
    $variantColor->product_id = $product->id;
    $variantColor->sku = 'A10-CLR-001';
    $variantColor->name = 'A10 Color';
    $variantColor->price = $product->price * 1.5;  // Harga color print lebih mahal
    $variantColor->harga_beli = $product->harga_beli;
    $variantColor->paper_size = 'A4';
    $variantColor->print_type = 'color';
    $variantColor->stock = 50;
    $variantColor->min_stock_threshold = 5;
    $variantColor->is_active = true;
    $variantColor->weight = $product->weight;
    $variantColor->length = $product->length;
    $variantColor->width = $product->width;
    $variantColor->height = $product->height;
    $variantColor->save();
    
    echo "\nVariant color juga berhasil dibuat!\n";
    echo "- Variant ID: " . $variantColor->id . "\n";
    echo "- Name: " . $variantColor->name . "\n";
    echo "- Paper Size: " . $variantColor->paper_size . "\n";
    echo "- Print Type: " . $variantColor->print_type . "\n";
    echo "- Stock: " . $variantColor->stock . "\n";
} else {
    echo "Produk tidak ditemukan\n";
}