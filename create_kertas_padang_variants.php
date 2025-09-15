<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

echo "Creating proper variants for Kertas Padang product...\n\n";

$product = Product::where('name', 'Kertas Padang')->first();

if($product) {
    echo "Found product: " . $product->name . "\n";
    
    // Create Padang Black & White variant
    $bwVariant = new ProductVariant([
        'product_id' => $product->id,
        'name' => 'Kertas Padang - Black & White',
        'sku' => 'KERTAS-PADANG-BW',
        'barcode' => 'KP-BW-' . time(),
        'paper_size' => 'A4',  // Must be one of: A4, A3, F4
        'print_type' => 'bw',  // Must be one of: bw, color
        'price' => 2000,       // Price per sheet
        'stock' => 100,        // Initial stock
        'is_active' => true
    ]);
    $bwVariant->save();
    
    echo "Created Black & White variant: " . $bwVariant->name . " (ID: " . $bwVariant->id . ")\n";
    
    // Create Padang Color variant
    $colorVariant = new ProductVariant([
        'product_id' => $product->id,
        'name' => 'Kertas Padang - Color',
        'sku' => 'KERTAS-PADANG-COLOR',
        'barcode' => 'KP-COLOR-' . time(),
        'paper_size' => 'A4',   // Must be one of: A4, A3, F4
        'print_type' => 'color', // Must be one of: bw, color
        'price' => 5000,        // Price per sheet
        'stock' => 50,          // Initial stock
        'is_active' => true
    ]);
    $colorVariant->save();
    
    echo "Created Color variant: " . $colorVariant->name . " (ID: " . $colorVariant->id . ")\n";
    
    echo "\nVariants created successfully!\n";
    echo "Now 'Kertas Padang' will show properly in Stock Management with Paper Size and Print Type columns filled.\n\n";
    
    // Verify the variants
    $product->load('variants');
    echo "Verification - Product now has " . $product->variants->count() . " variants:\n";
    foreach($product->variants as $variant) {
        echo "  - " . $variant->name . "\n";
        echo "    Paper Size: " . $variant->paper_size . "\n";
        echo "    Print Type: " . $variant->print_type . "\n";
        echo "    Stock: " . $variant->stock . "\n";
        echo "    Price: Rp " . number_format($variant->price) . "\n\n";
    }
    
} else {
    echo "Product 'Kertas Padang' not found!\n";
}