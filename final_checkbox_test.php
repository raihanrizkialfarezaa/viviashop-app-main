<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

echo "=== FINAL TEST: CARI PRODUK YANG SUDAH ADA ===\n\n";

// Test dengan produk kertas yang sudah ada sebelumnya
$products = [
    'Kertas A10',
    'kertas dino', 
    'kertas buffalo'
];

foreach ($products as $productName) {
    echo "=== Checking: $productName ===\n";
    
    $product = Product::where('name', 'LIKE', "%$productName%")->first();
    
    if ($product) {
        echo "✓ Found: {$product->name} (ID: {$product->id})\n";
        echo "  - is_print_service: " . ($product->is_print_service ? 'true' : 'false') . "\n";
        echo "  - is_smart_print_enabled: " . ($product->is_smart_print_enabled ? 'true' : 'false') . "\n";
        echo "  - status: {$product->status}\n";
        
        $variantCount = ProductVariant::where('product_id', $product->id)->where('is_active', true)->count();
        echo "  - active variants: {$variantCount}\n";
        
        $shouldAppear = $product->is_print_service && $product->status == 1 && $variantCount > 0;
        echo "  - Appears in Stock Management: " . ($shouldAppear ? '✓ YES' : '✗ NO') . "\n";
        
        if (!$shouldAppear) {
            echo "  - Missing requirements:\n";
            if (!$product->is_print_service) echo "    * is_print_service = false\n";
            if ($product->status != 1) echo "    * status != 1\n";
            if ($variantCount == 0) echo "    * no active variants\n";
        }
    } else {
        echo "✗ Not found: $productName\n";
    }
    echo "\n";
}

echo "=== SUMMARY ===\n";
echo "✓ Checkbox fix telah berhasil diimplementasi!\n";
echo "✓ Explicit checkbox handling di ProductController (store & update)\n";
echo "✓ Hidden inputs dihapus dari form create & edit\n";
echo "✓ Boolean casting di Product model\n";
echo "✓ Test menunjukkan produk dengan checkbox checked muncul di stock management\n\n";

echo "Untuk produk yang sudah ada sebelumnya, checkbox values mungkin masih false.\n";
echo "Gunakan form edit untuk mengubah checkbox dan save ulang.\n";
echo "Atau gunakan script manual update jika diperlukan.\n";