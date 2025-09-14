<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\StockMovement;
use App\Models\ProductVariant;
use App\Models\ProductInventory;

echo "=== FIXING ORDER #239 ISSUE ===\n\n";

// Current AMPLOP status
$amplopVariant = ProductVariant::where('product_id', 9)->first();
echo "Current AMPLOP stock: " . $amplopVariant->stock . "\n";

// Get Order #239 movement
$order239Movement = StockMovement::where('variant_id', $amplopVariant->id)
    ->where('reference_id', 239)
    ->where('reference_type', 'order')
    ->first();

if ($order239Movement) {
    echo "\nOrder #239 movement:\n";
    echo "- Created: " . $order239Movement->created_at . "\n";
    echo "- Quantity: " . $order239Movement->quantity . "\n";
    echo "- Old Stock: " . $order239Movement->old_stock . "\n";  
    echo "- New Stock: " . $order239Movement->new_stock . "\n";
    
    // The problem: old_stock was recorded as 55, but real stock was 60
    // This means old_stock was already reduced by 5 before recording
    
    echo "\nAnalysis:\n";
    echo "- Expected old_stock: 60 (after Order #238)\n";
    echo "- Recorded old_stock: " . $order239Movement->old_stock . " (already reduced by manual code)\n";
    echo "- This indicates double deduction!\n";
    
    // Fix: Delete this movement and let new StockService handle it properly
    echo "\nFix plan:\n";
    echo "1. Delete incorrect movement\n";
    echo "2. Restore stock to 60\n";
    echo "3. Create correct movement: 60 -> 55\n\n";
    
    echo "Proceeding with fix...\n";
    
    // Delete incorrect movement
    $order239Movement->delete();
    echo "✓ Deleted incorrect movement\n";
    
    // Restore stock to what it should be before Order #239 (60)
    $amplopVariant->stock = 60;
    $amplopVariant->save();
    
    $inventory = ProductInventory::where('product_id', 9)->first();
    if ($inventory) {
        $inventory->qty = 60;
        $inventory->save();
    }
    echo "✓ Restored stock to 60\n";
    
    // Create correct movement using StockService
    $correctMovement = app(\App\Services\StockService::class)->recordMovement(
        $amplopVariant->id,
        'out',
        5,
        'order',
        239,
        'Admin Offline Sale',
        'Order #239 (corrected)'
    );
    
    echo "✓ Created correct movement\n";
    echo "✓ New stock: " . $amplopVariant->fresh()->stock . "\n";
    
} else {
    echo "No movement found for Order #239\n";
}