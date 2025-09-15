<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

echo "Testing Smart Print Variant Manager filters (final test)...\n\n";

// Simulate exactly what the controller does
echo "=== Problematic Variants (missing paper_size or print_type) ===\n";
$problematicVariants = ProductVariant::where('is_active', true)
    ->whereHas('product', function($query) {
        $query->where('is_print_service', true)
              ->where('status', 1);
    })
    ->where(function($query) {
        $query->whereNull('paper_size')->orWhereNull('print_type');
    })
    ->with('product')
    ->get();

echo "Found {$problematicVariants->count()} variants with missing print fields:\n";
foreach($problematicVariants as $variant) {
    echo "- {$variant->product->name} → {$variant->name}\n";
    echo "  Paper Size: " . ($variant->paper_size ?? 'Missing') . "\n";
    echo "  Print Type: " . ($variant->print_type ?? 'Missing') . "\n\n";
}

echo "=== Products Without Variants ===\n";
$productsWithoutVariants = Product::where('is_print_service', true)
    ->where('status', 1)
    ->whereDoesntHave('variants')
    ->get();

echo "Found {$productsWithoutVariants->count()} print service products without variants:\n";
foreach($productsWithoutVariants as $product) {
    echo "- {$product->name}\n";
}

echo "\n=== All Print Service Products (for verification) ===\n";
$allPrintServiceProducts = Product::where('is_print_service', true)
    ->where('status', 1)
    ->get();

echo "Total print service products: {$allPrintServiceProducts->count()}\n";
echo "Products with variants: " . ($allPrintServiceProducts->count() - $productsWithoutVariants->count()) . "\n";
echo "Products without variants: {$productsWithoutVariants->count()}\n";

// Test a few specific products
echo "\n=== Specific Product Check ===\n";
$kertas_padang = Product::where('name', 'Kertas Padang')->first();
$variant_count = ProductVariant::where('product_id', $kertas_padang->id)->count();
echo "Kertas Padang: {$variant_count} variants\n";

$test_checkbox = Product::where('name', 'Test Kertas Checkbox Fix')->first();
if($test_checkbox) {
    $variant_count2 = ProductVariant::where('product_id', $test_checkbox->id)->count();
    echo "Test Kertas Checkbox Fix: {$variant_count2} variants\n";
}