<?php

require_once 'vendor/autoload.php';

use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Fix Stock Issues ===\n\n";

try {
    // Step 1: Fix variant 46 stock_quantity
    echo "1. Fixing stock_quantity for variant 46...\n";
    
    $variant46 = ProductVariant::find(46);
    if ($variant46) {
        echo "Current stock_quantity: " . ($variant46->stock_quantity ?? 'NULL') . "\n";
        
        // Set initial stock to 10000 if NULL
        if (is_null($variant46->stock_quantity)) {
            $variant46->update(['stock_quantity' => 10000]);
            echo "✓ Set initial stock to 10000\n";
        }
        
        // Calculate actual stock based on movements
        $totalOut = StockMovement::where('variant_id', 46)
                                ->where('type', 'out')
                                ->sum('quantity');
        
        $totalIn = StockMovement::where('variant_id', 46)
                               ->where('type', 'in')
                               ->sum('quantity');
        
        echo "Total IN: {$totalIn}\n";
        echo "Total OUT: {$totalOut}\n";
        
        if ($totalOut > 0) {
            $correctStock = 10000 - $totalOut + $totalIn;
            $variant46->update(['stock_quantity' => $correctStock]);
            echo "✓ Updated stock to correct value: {$correctStock}\n";
        }
        
        $variant46->refresh();
        echo "Final stock_quantity: {$variant46->stock_quantity}\n\n";
    }
    
    // Step 2: Fix existing stock movements with missing data
    echo "2. Fixing stock movements with missing type/quantity...\n";
    
    $brokenMovements = StockMovement::where('variant_id', 46)
                                   ->whereNull('type')
                                   ->orWhereNull('quantity')
                                   ->get();
    
    echo "Found " . $brokenMovements->count() . " broken movements\n";
    
    foreach ($brokenMovements as $movement) {
        echo "Fixing movement ID {$movement->id}...\n";
        
        // Assuming these are 'out' movements for print orders
        if (strpos($movement->reference_type, 'print_order') !== false) {
            $movement->update([
                'type' => 'out',
                'quantity' => 1, // Default 1 page
                'quantity_before' => $movement->quantity_before ?? 10000,
                'quantity_after' => ($movement->quantity_before ?? 10000) - 1
            ]);
            echo "✓ Fixed as 'out' movement with quantity 1\n";
        }
    }
    
    // Step 3: Check and update all print service variants
    echo "\n3. Checking all print service variants stock...\n";
    
    $printVariants = ProductVariant::whereHas('product', function($q) {
        $q->where('is_print_service', true);
    })->get();
    
    foreach ($printVariants as $variant) {
        if (is_null($variant->stock_quantity)) {
            $variant->update(['stock_quantity' => 10000]);
            echo "✓ Set stock for variant {$variant->id} ({$variant->name}) to 10000\n";
        }
    }
    
    // Step 4: Test stock reduction logic
    echo "\n4. Testing stock reduction logic...\n";
    
    // Check if StockService has proper methods
    $stockService = app('App\\Services\\StockService');
    
    if (method_exists($stockService, 'reduceStock')) {
        echo "✓ StockService::reduceStock method exists\n";
        
        // Test stock reduction
        $currentStock = $variant46->stock_quantity;
        echo "Current stock before test: {$currentStock}\n";
        
        try {
            $stockService->reduceStock($variant46, 1, 'test_reduction', 999999);
            
            $variant46->refresh();
            $newStock = $variant46->stock_quantity;
            echo "Stock after test reduction: {$newStock}\n";
            
            if ($newStock == $currentStock - 1) {
                echo "✅ Stock reduction working correctly!\n";
                
                // Restore stock
                $stockService->adjustStock($variant46, $currentStock, 'restore_test', 999999);
                echo "✓ Stock restored\n";
            } else {
                echo "❌ Stock reduction not working properly\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Stock reduction failed: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ StockService::reduceStock method missing\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Fix Complete ===\n";