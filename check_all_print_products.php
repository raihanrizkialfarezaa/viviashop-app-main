<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

echo "Checking all products with smart_print or is_print_service...\n\n";

$products = Product::where(function($query) {
    $query->where('is_smart_print_enabled', 1)->orWhere('is_print_service', 1);
})->with('variants')->get();

foreach($products as $product) {
    echo "Product: " . $product->name . "\n";
    echo "Smart Print: " . ($product->is_smart_print_enabled ? 'Yes' : 'No') . "\n";
    echo "Is Print Service: " . ($product->is_print_service ? 'Yes' : 'No') . "\n";
    echo "Variants Count: " . $product->variants->count() . "\n";
    
    foreach($product->variants as $variant) {
        echo "  - Variant: " . $variant->name . " (ID: " . $variant->id . ")\n";
        echo "    Paper Size: " . ($variant->paper_size ?? 'NULL') . "\n";
        echo "    Print Type: " . ($variant->print_type ?? 'NULL') . "\n";
        echo "    Stock: " . $variant->stock . "\n\n";
    }
    echo "---\n\n";
}

echo "\nChecking all ProductVariants with paper_size or print_type...\n\n";

$variants = ProductVariant::whereNotNull('paper_size')->orWhereNotNull('print_type')->with('product')->get();

foreach($variants as $variant) {
    echo "Variant: " . $variant->name . " (Product: " . $variant->product->name . ")\n";
    echo "  Paper Size: " . ($variant->paper_size ?? 'NULL') . "\n";
    echo "  Print Type: " . ($variant->print_type ?? 'NULL') . "\n";
    echo "  Stock: " . $variant->stock . "\n\n";
}