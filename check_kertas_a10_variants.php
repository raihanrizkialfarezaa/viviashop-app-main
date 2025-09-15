<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

echo "Memeriksa product variants untuk KERTAS A10...\n";
$product = Product::where('name', 'like', '%Kertas A10%')->first();

if ($product) {
    echo "Produk ID: " . $product->id . "\n";
    echo "Mencari variants...\n";
    
    $variants = ProductVariant::where('product_id', $product->id)->get();
    
    if ($variants->count() > 0) {
        echo "Ditemukan " . $variants->count() . " variant(s):\n";
        foreach ($variants as $variant) {
            echo "- Variant ID: " . $variant->id . "\n";
            echo "  Name: " . $variant->name . "\n";
            echo "  Paper Size: " . $variant->paper_size . "\n";
            echo "  Print Type: " . $variant->print_type . "\n";
            echo "  Stock: " . $variant->stock . "\n";
            echo "  Is Active: " . ($variant->is_active ? 'YES' : 'NO') . "\n";
            echo "  Min Stock Threshold: " . $variant->min_stock_threshold . "\n";
            echo "  ---\n";
        }
    } else {
        echo "Tidak ada variants ditemukan untuk produk ini.\n";
        echo "Membuat variant default...\n";
        
        $variant = new ProductVariant();
        $variant->product_id = $product->id;
        $variant->name = 'A10 Standard';
        $variant->paper_size = 'A10';
        $variant->print_type = 'standard';
        $variant->stock = 100;
        $variant->min_stock_threshold = 10;
        $variant->is_active = true;
        $variant->save();
        
        echo "Variant default berhasil dibuat!\n";
        echo "- Variant ID: " . $variant->id . "\n";
        echo "- Name: " . $variant->name . "\n";
        echo "- Stock: " . $variant->stock . "\n";
    }
} else {
    echo "Produk tidak ditemukan\n";
}