<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

echo "Debugging Kertas Padang variants...\n\n";

$product = Product::where('name', 'Kertas Padang')->with('variants')->first();

if($product) {
    echo "Product: {$product->name}\n";
    echo "is_print_service: " . ($product->is_print_service ? 'true' : 'false') . "\n";
    echo "status: {$product->status}\n";
    echo "Total variants: " . $product->variants->count() . "\n\n";
    
    foreach($product->variants as $variant) {
        echo "Variant: {$variant->name} (ID: {$variant->id})\n";
        echo "  SKU: {$variant->sku}\n";
        echo "  is_active: " . ($variant->is_active ? 'true' : 'false') . "\n";
        echo "  paper_size: " . ($variant->paper_size ?? 'NULL') . "\n";
        echo "  print_type: " . ($variant->print_type ?? 'NULL') . "\n";
        echo "  stock: {$variant->stock}\n\n";
    }
    
    // Check which variants would be returned by our query
    echo "=== Checking filter query ===\n";
    $filteredVariants = ProductVariant::where('is_active', true)
        ->whereHas('product', function($query) {
            $query->where('name', 'Kertas Padang')
                  ->where('is_print_service', true)
                  ->where('status', 1);
        })
        ->with('product')
        ->get();
        
    echo "Filtered variants count: " . $filteredVariants->count() . "\n";
    foreach($filteredVariants as $variant) {
        echo "- {$variant->name}\n";
    }
} else {
    echo "Product 'Kertas Padang' not found\n";
}