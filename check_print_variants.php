<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Checking existing print service data...\n";

$product = \App\Models\Product::where('is_print_service', true)->first();
if ($product) {
    echo "Product: " . $product->name . "\n";
    echo "Variants: " . $product->variants->count() . "\n";
    
    if ($product->variants->count() > 0) {
        $variant = $product->variants->first();
        echo "First variant: ID " . $variant->id . ", Price: " . $variant->price . "\n";
    }
} else {
    echo "No print service product found\n";
}

$allVariants = \App\Models\ProductVariant::whereHas('product', function($q) {
    $q->where('is_print_service', true);
})->get();

echo "Total print service variants: " . $allVariants->count() . "\n";
foreach ($allVariants as $v) {
    echo "  Variant ID: " . $v->id . " - " . ($v->paper_size ?? 'No paper_size') . " " . ($v->print_type ?? 'No print_type') . " - Rp " . $v->price . "\n";
}

?>
