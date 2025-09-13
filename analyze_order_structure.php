<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=== ANALYZING ORDER TABLE STRUCTURE ===\n\n";

echo "1. CHECKING ORDERS TABLE COLUMNS...\n";
$columns = Schema::getColumnListing('orders');

$shippingColumns = array_filter($columns, function($column) {
    return strpos($column, 'shipping') !== false;
});

echo "Total columns: " . count($columns) . "\n";
echo "Shipping-related columns:\n";
foreach ($shippingColumns as $column) {
    echo "- {$column}\n";
}

echo "\n2. SAMPLE ORDER DATA...\n";
$sampleOrder = DB::table('orders')->latest()->first();

if ($sampleOrder) {
    echo "Sample order ID: {$sampleOrder->id}\n";
    foreach ($shippingColumns as $column) {
        $value = $sampleOrder->{$column} ?? 'NULL';
        echo "- {$column}: {$value}\n";
    }
}

echo "\n3. ORDERS TABLE STRUCTURE...\n";
$tableInfo = DB::select("DESCRIBE orders");

foreach ($tableInfo as $column) {
    if (strpos($column->Field, 'shipping') !== false || strpos($column->Field, 'courier') !== false) {
        echo "Column: {$column->Field}, Type: {$column->Type}, Null: {$column->Null}, Default: {$column->Default}\n";
    }
}

echo "\n=== ANALYSIS COMPLETE ===\n";