<?php

require_once 'vendor/autoload.php';

use App\Models\ProductVariant;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Fix Stock Field Mismatch ===\n\n";

try {
    // Test access to 'stock' field instead of 'stock_quantity'
    echo "1. Testing correct field access:\n";
    
    $variant46 = ProductVariant::find(46);
    
    echo "Trying stock_quantity: " . var_export($variant46->stock_quantity, true) . "\n";
    echo "Trying stock: " . var_export($variant46->stock, true) . "\n\n";
    
    // Update using correct field name
    echo "2. Updating stock using correct field name:\n";
    
    $currentStock = $variant46->stock;
    echo "Current stock value: {$currentStock}\n";
    
    // Calculate correct stock based on movements
    $totalOut = \App\Models\StockMovement::where('variant_id', 46)
                                        ->where('movement_type', 'out')
                                        ->sum('quantity');
    
    $correctStock = 10000 - $totalOut;
    echo "Calculated correct stock: {$correctStock}\n";
    
    // Update using the correct field
    $variant46->update(['stock' => $correctStock]);
    $variant46->refresh();
    
    echo "Updated stock value: {$variant46->stock}\n\n";
    
    // 3. Update all print service variants
    echo "3. Updating all print service variants to use 'stock' field:\n";
    
    $printVariants = ProductVariant::whereHas('product', function($q) {
        $q->where('is_print_service', true);
    })->get();
    
    foreach ($printVariants as $variant) {
        $totalOut = \App\Models\StockMovement::where('variant_id', $variant->id)
                                            ->where('movement_type', 'out')
                                            ->sum('quantity');
        
        $totalIn = \App\Models\StockMovement::where('variant_id', $variant->id)
                                           ->where('movement_type', 'in')
                                           ->sum('quantity');
        
        $correctStock = 10000 - $totalOut + $totalIn;
        
        $variant->update(['stock' => $correctStock]);
        
        echo "Variant {$variant->id}: Updated stock to {$correctStock}\n";
    }
    
    // 4. Test the fix
    echo "\n4. Testing fix:\n";
    $variant46->refresh();
    echo "A4 Color variant stock: {$variant46->stock}\n";
    
    if ($variant46->stock == 9998) {
        echo "✅ Stock now showing correct value 9998!\n";
    } else {
        echo "❌ Stock still not correct: {$variant46->stock}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Fix Complete ===\n";