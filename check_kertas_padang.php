<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

echo "Checking Kertas Padang product variants...\n\n";

$product = Product::where('name', 'like', '%Kertas Padang%')->with('variants')->first();

if($product) {
    echo "Product: " . $product->name . "\n";
    echo "Smart Print: " . ($product->smart_print ? 'Yes' : 'No') . "\n";
    echo "Is Print Service: " . ($product->is_print_service ? 'Yes' : 'No') . "\n\n";
    
    foreach($product->variants as $variant) {
        echo "Variant ID: " . $variant->id . "\n";
        echo "  - Name: " . $variant->name . "\n";
        echo "  - Paper Size: " . ($variant->paper_size ?? 'NULL') . "\n";
        echo "  - Print Type: " . ($variant->print_type ?? 'NULL') . "\n";
        echo "  - Stock: " . $variant->stock . "\n";
        echo "  - Price: " . $variant->price . "\n\n";
    }
} else {
    echo "Product not found\n";
}