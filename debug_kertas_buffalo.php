<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StockManagementService;

echo "=== DEBUG: KERTAS BUFFALO PRODUCT ANALYSIS ===\n\n";

// Step 1: Find the "kertas buffalo" product
echo "1. Mencari produk 'kertas buffalo'...\n";
$kertasBuffalo = Product::where('name', 'like', '%buffalo%')->orWhere('name', 'like', '%kertas buffalo%')->first();

if (!$kertasBuffalo) {
    echo "❌ Produk tidak ditemukan dengan nama mengandung 'buffalo'\n";
    echo "Mencari produk terbaru yang dibuat...\n";
    $latestProducts = Product::orderBy('created_at', 'desc')->limit(10)->get();
    echo "10 produk terbaru:\n";
    foreach ($latestProducts as $product) {
        echo "- ID: " . $product->id . " | Name: " . $product->name . " | Type: " . $product->type . " | Created: " . $product->created_at . "\n";
    }
    exit;
}

echo "✓ Produk ditemukan: " . $kertasBuffalo->name . " (ID: " . $kertasBuffalo->id . ")\n\n";

// Step 2: Check product properties
echo "2. Memeriksa properties produk:\n";
echo "   - Type: " . $kertasBuffalo->type . "\n";
echo "   - Status: " . $kertasBuffalo->status . " (" . ($kertasBuffalo->status == 1 ? 'ACTIVE' : 'INACTIVE') . ")\n";
echo "   - is_print_service: " . ($kertasBuffalo->is_print_service ? 'YES' : 'NO') . "\n";
echo "   - is_smart_print_enabled: " . ($kertasBuffalo->is_smart_print_enabled ? 'YES' : 'NO') . "\n";
echo "   - Created at: " . $kertasBuffalo->created_at . "\n";
echo "   - Updated at: " . $kertasBuffalo->updated_at . "\n\n";

// Step 3: Check variants in detail
echo "3. Memeriksa product variants (DETAIL):\n";
$variants = ProductVariant::where('product_id', $kertasBuffalo->id)->get();
echo "   Total variants: " . $variants->count() . "\n\n";

if ($variants->count() == 0) {
    echo "   ❌ MASALAH: Tidak ada variants!\n";
    echo "   Produk configurable atau simple yang digunakan untuk stock management harus punya variants.\n\n";
} else {
    foreach ($variants as $variant) {
        echo "   === Variant " . $variant->id . " ===\n";
        echo "   - Name: " . $variant->name . "\n";
        echo "   - SKU: " . $variant->sku . "\n";
        echo "   - Stock: " . $variant->stock . "\n";
        echo "   - Price: " . $variant->price . "\n";
        echo "   - Paper Size: " . ($variant->paper_size ?? 'NULL') . "\n";
        echo "   - Print Type: " . ($variant->print_type ?? 'NULL') . "\n";
        echo "   - Is Active: " . ($variant->is_active ? 'YES' : 'NO') . "\n";
        echo "   - Min Stock Threshold: " . ($variant->min_stock_threshold ?? 'NULL') . "\n";
        echo "   - Created: " . $variant->created_at . "\n";
        echo "   - Updated: " . $variant->updated_at . "\n";
        echo "   ----\n\n";
    }
}

// Step 4: Test StockManagementService filter step by step
echo "4. Testing StockManagementService Filter (STEP BY STEP):\n";

// Raw query untuk debug
echo "   a) Raw query test - semua product variants:\n";
$allProductVariants = ProductVariant::with('product')->get();
echo "      Total semua product variants di database: " . $allProductVariants->count() . "\n";

echo "\n   b) Filter step 1 - variants yang is_active = true:\n";
$activeVariants = ProductVariant::where('is_active', true)->with('product')->get();
echo "      Active variants: " . $activeVariants->count() . "\n";

echo "\n   c) Filter step 2 - dengan produk is_print_service = true:\n";
$printServiceVariants = ProductVariant::where('is_active', true)
    ->with('product')
    ->whereHas('product', function($query) {
        $query->where('is_print_service', true);
    })
    ->get();
echo "      Print service variants: " . $printServiceVariants->count() . "\n";

echo "\n   d) Filter step 3 - dengan produk status = 1:\n";
$finalVariants = ProductVariant::where('is_active', true)
    ->with('product')
    ->whereHas('product', function($query) {
        $query->where('is_print_service', true)
              ->where('status', 1);
    })
    ->get();
echo "      Final filtered variants: " . $finalVariants->count() . "\n";

// Check if buffalo variants are in each step
$buffaloVariants = $variants->where('is_active', true);
echo "\n   e) Kertas Buffalo dalam setiap filter:\n";
echo "      - Active buffalo variants: " . $buffaloVariants->count() . "\n";
foreach ($buffaloVariants as $variant) {
    $inPrintService = $printServiceVariants->where('id', $variant->id)->count() > 0;
    $inFinal = $finalVariants->where('id', $variant->id)->count() > 0;
    echo "        Variant " . $variant->id . ": Print Service Filter = " . ($inPrintService ? 'YES' : 'NO') . ", Final Filter = " . ($inFinal ? 'YES' : 'NO') . "\n";
}

// Step 5: Use actual StockManagementService
echo "\n5. Testing dengan StockManagementService actual:\n";
$stockService = new StockManagementService();
$stockVariants = $stockService->getVariantsByStock('asc');
$buffaloInStock = $stockVariants->filter(function($variant) use ($kertasBuffalo) {
    return $variant->product_id == $kertasBuffalo->id;
});

echo "   Stock service total variants: " . $stockVariants->count() . "\n";
echo "   Buffalo variants dalam stock service: " . $buffaloInStock->count() . "\n";

// Step 6: Check what's missing and suggest fix
echo "\n6. DIAGNOSIS & SOLUSI:\n";
$issues = [];

if ($kertasBuffalo->status != 1) {
    $issues[] = "❌ Product status = " . $kertasBuffalo->status . " (harus = 1)";
}

if (!$kertasBuffalo->is_print_service) {
    $issues[] = "❌ is_print_service = false (harus = true)";
}

if (!$kertasBuffalo->is_smart_print_enabled) {
    $issues[] = "❌ is_smart_print_enabled = false (harus = true untuk smart print)";
}

if ($variants->where('is_active', true)->count() == 0) {
    $issues[] = "❌ Tidak ada variants yang aktif";
}

if (count($issues) == 0) {
    echo "   ✅ Semua requirements terpenuhi - produk SEHARUSNYA muncul!\n";
    echo "   Kemungkinan issue: Cache atau browser perlu refresh\n";
} else {
    echo "   ISSUES DITEMUKAN:\n";
    foreach ($issues as $issue) {
        echo "   " . $issue . "\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Produk: " . $kertasBuffalo->name . "\n";
echo "Requirements checklist:\n";
echo "  " . ($kertasBuffalo->status == 1 ? '✅' : '❌') . " Status = 1\n";
echo "  " . ($kertasBuffalo->is_print_service ? '✅' : '❌') . " is_print_service = true\n";
echo "  " . ($kertasBuffalo->is_smart_print_enabled ? '✅' : '❌') . " is_smart_print_enabled = true\n";
echo "  " . ($variants->where('is_active', true)->count() > 0 ? '✅' : '❌') . " Has active variants\n";
echo "  " . ($buffaloInStock->count() > 0 ? '✅' : '❌') . " Muncul di stock management\n";

echo "\n=== SELESAI ===\n";