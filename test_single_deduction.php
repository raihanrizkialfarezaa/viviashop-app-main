<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\StockMovement;
use App\Models\ProductVariant;
use App\Models\ProductInventory;

echo "=== TESTING SINGLE DEDUCTION ===\n\n";

// Current AMPLOP status
$amplopVariant = ProductVariant::where('product_id', 9)->first();
$currentStock = $amplopVariant->stock;
echo "Current AMPLOP stock: $currentStock\n";

// Get the latest StockService instance
$stockService = app(\App\Services\StockService::class);

echo "\nTesting StockService.recordMovement for 3 units...\n";

// Test recordMovement - should do ONE deduction only
$movement = $stockService->recordMovement(
    $amplopVariant->id,
    'out',
    3,
    'order',
    999, // test order ID
    'Admin Offline Sale',
    'Testing single deduction'
);

// Check final stock
$amplopVariant = $amplopVariant->fresh();
$newStock = $amplopVariant->stock;

echo "After StockService.recordMovement:\n";
echo "- Old stock: $currentStock\n";
echo "- New stock: $newStock\n";
echo "- Difference: " . ($currentStock - $newStock) . "\n";
echo "- Expected difference: 3\n";

if (($currentStock - $newStock) == 3) {
    echo "✓ SUCCESS: Single deduction working correctly!\n";
} else {
    echo "✗ ERROR: Still having deduction issues\n";
}

// Check movement record
echo "\nMovement record:\n";
echo "- ID: " . $movement->id . "\n";
echo "- Old Stock: " . $movement->old_stock . "\n";
echo "- New Stock: " . $movement->new_stock . "\n";
echo "- Quantity: " . $movement->quantity . "\n";

// Cleanup test movement
echo "\nCleaning up test...\n";
$movement->delete();

// Restore original stock
$amplopVariant->stock = $currentStock;
$amplopVariant->save();

$inventory = ProductInventory::where('product_id', 9)->first();
if ($inventory) {
    $inventory->qty = $currentStock;
    $inventory->save();
}

echo "✓ Test cleaned up, stock restored to $currentStock\n";