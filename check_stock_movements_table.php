<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== STOCK MOVEMENTS TABLE STRUCTURE ===\n\n";

$columns = DB::select('DESCRIBE stock_movements');
foreach ($columns as $column) {
    echo "- {$column->Field}: {$column->Type} " . ($column->Null === 'YES' ? '(nullable)' : '(not null)') . "\n";
}

echo "\n=== RECENT STOCK MOVEMENTS ===\n\n";
$movements = DB::table('stock_movements')->orderBy('id', 'desc')->limit(10)->get();
foreach ($movements as $movement) {
    echo "ID: {$movement->id}, Variant: {$movement->variant_id}, Type: {$movement->movement_type}, Qty: {$movement->quantity}\n";
    echo "  Old Stock: {$movement->old_stock} → New Stock: {$movement->new_stock}\n";
    if (isset($movement->reason)) echo "  Reason: {$movement->reason}\n";
    if (isset($movement->reference_type)) echo "  Reference Type: {$movement->reference_type}\n";
    if (isset($movement->reference_id)) echo "  Reference ID: {$movement->reference_id}\n";
    if (isset($movement->notes)) echo "  Notes: {$movement->notes}\n";
    echo "  Created: {$movement->created_at}\n\n";
}

echo "=== DONE ===\n";