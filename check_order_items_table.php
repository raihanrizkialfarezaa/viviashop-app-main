<?php

require_once './vendor/autoload.php';

use Illuminate\Support\Facades\DB;

$app = require_once './bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== ORDER ITEMS TABLE STRUCTURE ===\n\n";

$columns = DB::select('DESCRIBE order_items');

foreach($columns as $col) {
    $nullable = $col->Null === 'YES' ? 'NULL' : 'NOT NULL';
    $default = $col->Default !== null ? "DEFAULT '{$col->Default}'" : 'NO DEFAULT';
    echo "{$col->Field} - {$col->Type} - {$nullable} - {$default}\n";
}

echo "\n=== OrderItem MODEL FILLABLE CHECK ===\n";

$fillable = \App\Models\OrderItem::getFillable();
echo "Fillable fields:\n";
foreach($fillable as $field) {
    echo "  - $field\n";
}

echo "\n=== ANALYSIS ===\n";
$skuColumn = collect($columns)->firstWhere('Field', 'sku');
if ($skuColumn) {
    echo "SKU field found:\n";
    echo "  Type: {$skuColumn->Type}\n";
    echo "  Nullable: {$skuColumn->Null}\n";
    echo "  Default: " . ($skuColumn->Default ?? 'NULL') . "\n";
    
    if ($skuColumn->Null === 'NO' && $skuColumn->Default === null) {
        echo "\n⚠️  ISSUE: SKU field is NOT NULL but has no default value\n";
        echo "This will cause INSERT errors if SKU is not provided\n";
    }
} else {
    echo "SKU field not found in order_items table\n";
}

echo "\n=== SOLUTION ANALYSIS ===\n";
echo "1. Make SKU nullable OR provide default value\n";
echo "2. Always include SKU in order item creation\n";
echo "3. Fix _saveOrderItems method to handle missing SKU\n";