<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StockManagementService;

echo "=== DEBUG: KERTAS DINO PRODUCT ANALYSIS ===\n\n";

// Step 1: Find the "kertas dino" product
echo "1. Mencari produk 'kertas dino'...\n";
$kertasDino = Product::where('name', 'like', '%kertas dino%')->orWhere('name', 'like', '%dino%')->first();

if (!$kertasDino) {
    echo "❌ Produk tidak ditemukan dengan nama mengandung 'kertas dino' atau 'dino'\n";
    echo "Mencari produk terbaru yang dibuat...\n";
    $latestProducts = Product::orderBy('created_at', 'desc')->limit(5)->get();
    echo "5 produk terbaru:\n";
    foreach ($latestProducts as $product) {
        echo "- ID: " . $product->id . " | Name: " . $product->name . " | Created: " . $product->created_at . "\n";
    }
    exit;
}

echo "✓ Produk ditemukan: " . $kertasDino->name . " (ID: " . $kertasDino->id . ")\n\n";

// Step 2: Check product properties
echo "2. Memeriksa properties produk:\n";
echo "   - Type: " . $kertasDino->type . "\n";
echo "   - Status: " . $kertasDino->status . " (" . ($kertasDino->status == 1 ? 'ACTIVE' : 'INACTIVE') . ")\n";
echo "   - is_print_service: " . ($kertasDino->is_print_service ? 'YES' : 'NO') . "\n";
echo "   - is_smart_print_enabled: " . ($kertasDino->is_smart_print_enabled ? 'YES' : 'NO') . "\n";
echo "   - Created at: " . $kertasDino->created_at . "\n\n";

// Step 3: Check variants
echo "3. Memeriksa product variants:\n";
$variants = ProductVariant::where('product_id', $kertasDino->id)->get();
echo "   Total variants: " . $variants->count() . "\n";

if ($variants->count() == 0) {
    echo "   ❌ MASALAH: Tidak ada variants!\n";
    echo "   Untuk muncul di stock management, produk harus punya minimal 1 variant aktif.\n\n";
} else {
    foreach ($variants as $variant) {
        echo "   - Variant ID: " . $variant->id . "\n";
        echo "     Name: " . $variant->name . "\n";
        echo "     SKU: " . $variant->sku . "\n";
        echo "     Stock: " . $variant->stock . "\n";
        echo "     Paper Size: " . $variant->paper_size . "\n";
        echo "     Print Type: " . $variant->print_type . "\n";
        echo "     Is Active: " . ($variant->is_active ? 'YES' : 'NO') . "\n";
        echo "     ---\n";
    }
}

// Step 4: Test StockManagementService filter
echo "4. Testing StockManagementService filter:\n";
$stockService = new StockManagementService();
$allVariants = $stockService->getVariantsByStock('asc');

$kertasDinoVariants = $allVariants->filter(function($variant) use ($kertasDino) {
    return $variant->product_id == $kertasDino->id;
});

echo "   Total variants dalam stock service: " . $allVariants->count() . "\n";
echo "   Kertas dino variants dalam stock service: " . $kertasDinoVariants->count() . "\n\n";

// Step 5: Manual check untuk requirements
echo "5. Manual check requirements untuk muncul di stock:\n";
$requirements = [
    'Product Status = 1' => $kertasDino->status == 1,
    'is_print_service = true' => $kertasDino->is_print_service == true,
    'Has active variants' => $variants->where('is_active', true)->count() > 0
];

foreach ($requirements as $requirement => $status) {
    echo "   " . ($status ? '✓' : '❌') . " " . $requirement . "\n";
}

// Step 6: Check what's missing and suggest fix
echo "\n6. DIAGNOSIS & SOLUSI:\n";

if (!$kertasDino->is_print_service) {
    echo "❌ MASALAH: is_print_service = false\n";
    echo "   SOLUSI: Update is_print_service = true\n";
}

if (!$kertasDino->is_smart_print_enabled) {
    echo "❌ MASALAH: is_smart_print_enabled = false\n";
    echo "   SOLUSI: Update is_smart_print_enabled = true\n";
}

if ($variants->count() == 0) {
    echo "❌ MASALAH: Tidak ada variants\n";
    echo "   SOLUSI: Buat variants untuk produk ini\n";
} elseif ($variants->where('is_active', true)->count() == 0) {
    echo "❌ MASALAH: Tidak ada variants yang aktif\n";
    echo "   SOLUSI: Set minimal 1 variant menjadi active\n";
}

// Step 7: Show current working smart print products for comparison
echo "\n7. Perbandingan dengan produk smart print yang working:\n";
$workingSmartPrint = Product::where('is_print_service', true)
    ->where('is_smart_print_enabled', true)
    ->where('status', 1)
    ->whereHas('productVariants', function($query) {
        $query->where('is_active', true);
    })
    ->limit(3)
    ->get();

foreach ($workingSmartPrint as $product) {
    $variantCount = $product->productVariants()->where('is_active', true)->count();
    echo "   ✓ " . $product->name . " (" . $variantCount . " active variants)\n";
}

echo "\n=== SELESAI ===\n";