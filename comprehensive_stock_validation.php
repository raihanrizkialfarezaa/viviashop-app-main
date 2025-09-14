<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== COMPREHENSIVE STOCK FIX VALIDATION ===\n";

// Test current state
echo "\n1. CURRENT STATE VALIDATION\n";
$amplop = \App\Models\Product::with(['productInventory', 'productVariants'])->find(9);

echo "AMPLOP Product:\n";
echo "- ID: {$amplop->id}\n";
echo "- Name: {$amplop->name}\n";
echo "- Type: {$amplop->type}\n";
echo "- Current Stock: {$amplop->productInventory->qty}\n";
echo "- Variants: {$amplop->productVariants->count()}\n";

if ($amplop->productVariants->count() > 0) {
    foreach ($amplop->productVariants as $variant) {
        echo "  - Variant {$variant->id}: {$variant->name}, Stock: {$variant->stock}\n";
    }
}

// Check StockMovements
echo "\n2. STOCK MOVEMENT VALIDATION\n";
$stockMovements = \App\Models\StockMovement::whereHas('variant', function($q) {
    $q->where('product_id', 9);
})->orWhere('variant_id', $amplop->productVariants->pluck('id')->toArray())
  ->orderBy('created_at', 'desc')
  ->get();

echo "StockMovement records for AMPLOP: {$stockMovements->count()}\n";
foreach ($stockMovements as $movement) {
    echo "- {$movement->created_at}: {$movement->movement_type} {$movement->quantity} ({$movement->reason})\n";
    echo "  Old: {$movement->old_stock} → New: {$movement->new_stock}\n";
}

// Check RekamanStok
echo "\n3. REKAMAN STOK VALIDATION\n";
$rekamanStok = \App\Models\RekamanStok::where('product_id', 9)
                ->orderBy('created_at', 'desc')
                ->get();

echo "RekamanStok records for AMPLOP: {$rekamanStok->count()}\n";
foreach ($rekamanStok as $rekaman) {
    $stok_masuk = $rekaman->stok_masuk ?? 0;
    $stok_awal = $rekaman->stok_awal ?? 0; 
    $stok_sisa = $rekaman->stok_sisa ?? 0;
    echo "- {$rekaman->created_at}: Masuk={$stok_masuk}, Awal={$stok_awal}, Sisa={$stok_sisa}\n";
}

// Test the StockService directly
echo "\n4. TESTING STOCKSERVICE FOR SIMPLE PRODUCTS\n";
echo "Testing StockService.recordSimpleProductMovement...\n";

try {
    $movement = \App\Services\StockService::recordSimpleProductMovement(
        9, // AMPLOP product ID
        'in', // movement type
        5, // quantity
        'test',
        999,
        'manual_adjustment',
        'Testing simple product stock movement'
    );
    
    if ($movement) {
        echo "✅ StockMovement created successfully!\n";
        echo "- Movement ID: {$movement->id}\n";
        echo "- Variant ID: {$movement->variant_id}\n";
        echo "- Quantity: {$movement->quantity}\n";
        echo "- Old Stock: {$movement->old_stock} → New Stock: {$movement->new_stock}\n";
        
        // Check updated stock
        $amplop->refresh();
        echo "- Updated ProductInventory: {$amplop->productInventory->qty}\n";
        
        // Reverse the test movement
        echo "\nReversing test movement...\n";
        $reverseMovement = \App\Services\StockService::recordSimpleProductMovement(
            9,
            'out', 
            5,
            'test',
            999,
            'manual_adjustment',
            'Reversing test movement'
        );
        
        if ($reverseMovement) {
            echo "✅ Reverse movement successful!\n";
            $amplop->refresh();
            echo "- Final stock after reversal: {$amplop->productInventory->qty}\n";
        }
        
    } else {
        echo "❌ Failed to create StockMovement\n";
    }
} catch (Exception $e) {
    echo "❌ Error testing StockService: {$e->getMessage()}\n";
}

echo "\n5. VALIDATION SUMMARY\n";
$amplop->refresh();

$issues = [];
if ($amplop->productInventory->qty != 20) {
    $issues[] = "Stock quantity is {$amplop->productInventory->qty}, should be 20";
}

if ($amplop->productVariants->count() == 0) {
    $issues[] = "No variants available for StockMovement tracking";
}

$recentMovements = \App\Models\StockMovement::whereHas('variant', function($q) {
    $q->where('product_id', 9);
})->count();

if ($recentMovements == 0) {
    $issues[] = "No StockMovement records found for simple product tracking";
}

if (empty($issues)) {
    echo "✅ ALL TESTS PASSED!\n";
    echo "- Stock quantity is correct: {$amplop->productInventory->qty}\n";
    echo "- Default variant exists for tracking\n";
    echo "- StockMovement system works for simple products\n";
    echo "- No duplicate stock updates\n";
} else {
    echo "❌ Issues found:\n";
    foreach ($issues as $issue) {
        echo "- {$issue}\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";