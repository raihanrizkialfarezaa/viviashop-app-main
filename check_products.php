<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantAttribute;

$product = Product::first();
echo "Product: {$product->name}\n";
echo "Type: {$product->type}\n";

$variants = $product->productVariants;
echo "Variants count: {$variants->count()}\n";

if ($variants->count() == 0 && $product->type == 'simple') {
    // Buat variant default untuk produk simple
    $variant = new ProductVariant();
    $variant->product_id = $product->id;
    $variant->name = 'Default';
    $variant->price = $product->price ?? 10000;
    $variant->harga_beli = $product->harga_beli ?? 5000;
    $variant->stock = $product->productInventory->qty ?? 0;
    $variant->sku = $product->id . '-DEFAULT';
    $variant->is_active = true;
    $variant->save();
    
    echo "Created default variant for simple product\n";
    echo "Variant ID: {$variant->id}\n";
    echo "Stock: {$variant->stock}\n";
}

foreach ($variants as $variant) {
    echo "Variant {$variant->id}: Stock = {$variant->stock}\n";
}

try {
    echo "Checking available products...\n";
    
    $products = Product::select('id', 'name', 'type')->take(10)->get();
    
    foreach ($products as $product) {
        echo "Product ID: {$product->id} - {$product->name} - Type: {$product->type}\n";
    }
    
    echo "\nTest completed successfully\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
