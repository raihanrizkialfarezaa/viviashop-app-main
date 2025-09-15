<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

$app->boot();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING PRODUCT_VARIANTS TABLE STRUCTURE ===\n\n";

try {
    $columns = DB::select('DESCRIBE product_variants');
    
    echo "Columns in product_variants table:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type})\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== CHECKING RECENT VARIANT CREATION ===\n";

$recentVariants = DB::table('product_variants')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "Recent variants:\n";
foreach ($recentVariants as $variant) {
    echo "- ID: {$variant->id}, Name: {$variant->name}, Product ID: {$variant->product_id}\n";
}

echo "\n=== CHECKING RELATIONSHIPS ===\n";

// Check if the relationship is correct
$productWithVariants = DB::table('products')
    ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
    ->where('products.is_print_service', true)
    ->limit(3)
    ->get(['products.id as product_id', 'products.name', 'product_variants.name as variant_name']);

echo "Products with variants:\n";
foreach ($productWithVariants as $item) {
    echo "- Product: {$item->name} -> Variant: {$item->variant_name}\n";
}