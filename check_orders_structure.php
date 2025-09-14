<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING ORDERS TABLE STRUCTURE ===\n\n";

$columns = DB::select("DESCRIBE orders");

echo "Orders table columns:\n";
foreach ($columns as $column) {
    echo "- {$column->Field} ({$column->Type})\n";
}

echo "\nSample existing order:\n";
$sample = DB::table('orders')->first();
if ($sample) {
    foreach ((array)$sample as $key => $value) {
        echo "- $key: $value\n";
    }
}

echo "\n=== CHECKING ORDER ITEMS TABLE ===\n";
$itemColumns = DB::select("DESCRIBE order_items");

echo "Order_items table columns:\n";
foreach ($itemColumns as $column) {
    echo "- {$column->Field} ({$column->Type})\n";
}