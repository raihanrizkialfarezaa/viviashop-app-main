<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

echo "Testing updated filters for Smart Print Variant Manager...\n\n";

// Check products that are marked as print service
echo "=== Products with is_print_service = true and status = 1 ===\n";
$printServiceProducts = Product::where('is_print_service', true)
    ->where('status', 1)
    ->with('variants')
    ->get();

echo "Found " . $printServiceProducts->count() . " print service products:\n";
foreach($printServiceProducts as $product) {
    echo "- {$product->name} (Variants: {$product->variants->count()})\n";
}

echo "\n=== Variants from print service products with missing fields ===\n";
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

echo "Found " . $problematicVariants->count() . " variants with missing print fields:\n";
foreach($problematicVariants as $variant) {
    echo "- {$variant->product->name} → {$variant->name}\n";
    echo "  Paper Size: " . ($variant->paper_size ?? 'NULL') . ", Print Type: " . ($variant->print_type ?? 'NULL') . "\n";
}

echo "\n=== Print service products without variants ===\n";
$productsWithoutVariants = Product::where('is_print_service', true)
    ->where('status', 1)
    ->whereDoesntHave('variants')
    ->get();

echo "Found " . $productsWithoutVariants->count() . " print service products without variants:\n";
foreach($productsWithoutVariants as $product) {
    echo "- {$product->name}\n";
}