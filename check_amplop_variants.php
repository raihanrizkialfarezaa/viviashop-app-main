<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CHECKING AMPLOP VARIANTS ===\n";

$amplop = \App\Models\Product::with('productVariants')->find(9);
if (!$amplop) {
    echo "❌ AMPLOP product not found!\n";
    exit;
}

echo "Product: {$amplop->name}\n";
echo "Type: {$amplop->type}\n";
echo "Variants count: {$amplop->productVariants->count()}\n";

if ($amplop->productVariants->count() > 0) {
    echo "\nVariants:\n";
    foreach ($amplop->productVariants as $variant) {
        echo "- Variant ID: {$variant->id}, Stock: {$variant->stock}\n";
    }
} else {
    echo "\n❌ No variants found for AMPLOP!\n";
    echo "This is why StockMovement records can't be created.\n";
    
    echo "\n=== SOLUTION: CREATE DEFAULT VARIANT FOR SIMPLE PRODUCTS ===\n";
    echo "We need to either:\n";
    echo "1. Create a default variant for simple products, OR\n";
    echo "2. Modify StockMovement to support direct product_id\n";
}