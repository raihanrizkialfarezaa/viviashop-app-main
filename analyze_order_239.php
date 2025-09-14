<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\StockMovement;
use App\Models\ProductVariant;
use App\Models\Order;

echo "=== ANALYZING ORDER #239 ISSUE ===\n\n";

// Get AMPLOP variant
$amplopVariant = ProductVariant::where('product_id', 9)->first();
echo "Current AMPLOP stock: " . $amplopVariant->stock . "\n\n";

// Get Order #239 details
$order239 = Order::find(239);
if ($order239) {
    echo "ORDER #239:\n";
    echo "Status: " . $order239->status . "\n";
    echo "Created: " . $order239->created_at . "\n";
    echo "Items:\n";
    foreach ($order239->orderItems as $item) {
        echo "  - Product: " . $item->product->name . " | Qty: " . $item->qty . "\n";
    }
    echo "\n";
}

// Get all movements in chronological order for last 2 hours
echo "=== ALL RECENT AMPLOP MOVEMENTS (Last 2 hours) ===\n";
$recentMovements = StockMovement::where('variant_id', $amplopVariant->id)
    ->where('created_at', '>=', now()->subHours(2))
    ->orderBy('created_at', 'asc')
    ->get();

$runningStock = null;
foreach ($recentMovements as $movement) {
    if ($runningStock === null) {
        $runningStock = $movement->old_stock;
    }
    
    echo $movement->created_at->format('Y-m-d H:i:s') . " | " . 
         str_pad($movement->movement_type, 3) . " | " . 
         str_pad($movement->quantity, 3) . " | " . 
         "Old: " . str_pad($movement->old_stock, 3) . " | " . 
         "New: " . str_pad($movement->new_stock, 3) . " | " . 
         $movement->reference_type . "#" . $movement->reference_id . "\n";
    
    // Verify calculation
    if ($movement->movement_type == 'in') {
        $expected = $runningStock + $movement->quantity;
    } else {
        $expected = $runningStock - $movement->quantity;
    }
    
    if ($expected != $movement->new_stock) {
        echo "  ❌ CALCULATION ERROR: Expected " . $expected . " but got " . $movement->new_stock . "\n";
    }
    
    $runningStock = $movement->new_stock;
}

echo "\nFinal calculated stock: " . $runningStock . "\n";
echo "Actual stock in database: " . $amplopVariant->stock . "\n";

if ($runningStock != $amplopVariant->stock) {
    echo "❌ STOCK MISMATCH!\n";
} else {
    echo "✅ Stock is consistent with movements\n";
}