<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "=== CHECK VARIANTS STRUKTUR ===\n\n";

$testProduct = Product::find(152);
if (!$testProduct) {
    echo "Produk test tidak ditemukan\n";
    exit;
}

echo "Product: {$testProduct->name}\n";
echo "ID: {$testProduct->id}\n\n";

// Check variants relation
echo "=== VARIANTS RELATION ===\n";
$variants = $testProduct->variants;
echo "Variants count: " . $variants->count() . "\n";

if ($variants->count() > 0) {
    foreach ($variants as $v) {
        echo "- Variant ID {$v->id}: type={$v->type}, status={$v->status}\n";
        echo "  SKU: {$v->sku}\n";
        echo "  Name: {$v->name}\n\n";
    }
} else {
    echo "No variants found\n";
}

// Check dengan cara langsung dari database  
echo "=== CHECK LANGSUNG DARI DATABASE ===\n";
$directVariants = Product::where('parent_id', $testProduct->id)->get();
echo "Direct variants count: " . $directVariants->count() . "\n";

if ($directVariants->count() > 0) {
    foreach ($directVariants as $v) {
        echo "- Variant ID {$v->id}: type={$v->type}, status={$v->status}\n";
        echo "  SKU: {$v->sku}\n";
        echo "  Name: {$v->name}\n\n";
    }
}

// Check ProductVariant model exists
echo "=== CHECK PRODUCTVARIANT MODEL ===\n";
if (class_exists('App\\Models\\ProductVariant')) {
    echo "✓ ProductVariant model exists\n";
    
    $variantModel = new \App\Models\ProductVariant();
    $table = $variantModel->getTable();
    echo "Table: {$table}\n";
    
    try {
        $columns = DB::select("DESCRIBE {$table}");
        echo "Columns:\n";
        foreach ($columns as $col) {
            echo "- {$col->Field}\n";
        }
    } catch (Exception $e) {
        echo "Error getting table structure: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ ProductVariant model not found\n";
}