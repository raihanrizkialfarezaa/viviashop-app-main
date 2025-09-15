<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

echo "Checking ProductVariant table directly...\n\n";

// Get variants with SKUs that we know exist
$variants = ProductVariant::whereIn('sku', ['kfhks2424725', 'duiwru9479759'])->get();

foreach($variants as $variant) {
    echo "Variant: {$variant->name} (SKU: {$variant->sku})\n";
    echo "  ID: {$variant->id}\n";
    echo "  product_id: {$variant->product_id}\n";
    echo "  paper_size: " . ($variant->paper_size ?? 'NULL') . "\n";
    echo "  print_type: " . ($variant->print_type ?? 'NULL') . "\n";
    echo "  is_active: " . ($variant->is_active ? 'true' : 'false') . "\n";
    
    // Try to get product
    try {
        $product = $variant->product;
        if($product) {
            echo "  Product: {$product->name} (ID: {$product->id})\n";
            echo "  Product is_print_service: " . ($product->is_print_service ? 'true' : 'false') . "\n";
            echo "  Product status: {$product->status}\n";
        } else {
            echo "  Product: NULL (product_id {$variant->product_id} not found)\n";
        }
    } catch(\Exception $e) {
        echo "  Product: Error - " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "=== Direct database check ===\n";
$dbResults = DB::select("
    SELECT v.id, v.name, v.sku, v.product_id, v.is_active, v.paper_size, v.print_type,
           p.name as product_name, p.is_print_service, p.status
    FROM product_variants v 
    LEFT JOIN products p ON v.product_id = p.id 
    WHERE v.sku IN ('kfhks2424725', 'duiwru9479759')
");

foreach($dbResults as $row) {
    echo "Variant: {$row->name} (SKU: {$row->sku})\n";
    echo "  product_id: {$row->product_id}\n";
    echo "  Product: {$row->product_name}\n";
    echo "  is_active: {$row->is_active}\n";
    echo "  paper_size: {$row->paper_size}\n";
    echo "  print_type: {$row->print_type}\n";
    echo "  Product is_print_service: {$row->is_print_service}\n";
    echo "  Product status: {$row->status}\n\n";
}