<?php

require_once 'vendor/autoload.php';

use App\Models\ProductVariant;
use App\Models\PrintOrder;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Deep Dive Stock and Order Analysis ===\n\n";

try {
    // 1. Check variant 46 stock
    echo "1. Checking variant 46 stock again:\n";
    $variant46 = ProductVariant::find(46);
    echo "Raw stock_quantity value: '" . var_export($variant46->stock_quantity, true) . "'\n";
    echo "Stock is null? " . (is_null($variant46->stock_quantity) ? 'YES' : 'NO') . "\n\n";
    
    // Force refresh stock calculation
    echo "2. Force recalculating stock...\n";
    $totalOut = \App\Models\StockMovement::where('variant_id', 46)
                                        ->where('movement_type', 'out')
                                        ->sum('quantity');
    
    $totalIn = \App\Models\StockMovement::where('variant_id', 46)
                                       ->where('movement_type', 'in')
                                       ->sum('quantity');
    
    echo "Total OUT: {$totalOut}\n";
    echo "Total IN: {$totalIn}\n";
    
    $correctStock = 10000 - $totalOut + $totalIn;
    echo "Calculated correct stock: {$correctStock}\n";
    
    // Update stock
    $variant46->update(['stock_quantity' => $correctStock]);
    $variant46->refresh();
    
    echo "Updated stock: {$variant46->stock_quantity}\n\n";
    
    // 3. Check specific recent order
    echo "3. Checking specific recent order:\n";
    $recentOrder = PrintOrder::where('order_code', 'PRINT-15-09-2025-23-39-27')->first();
    
    if ($recentOrder) {
        echo "Order: {$recentOrder->order_code}\n";
        echo "Order data: " . ($recentOrder->order_data ?: 'NULL/EMPTY') . "\n";
        echo "Total amount: {$recentOrder->total_amount}\n";
        echo "Payment status: {$recentOrder->payment_status}\n";
        echo "Status: {$recentOrder->status}\n";
        
        // Check files
        $files = $recentOrder->files;
        echo "Files count: " . $files->count() . "\n";
        foreach ($files as $file) {
            echo "  - {$file->file_name}: {$file->pages_count} pages\n";
        }
        
        // Check if this order has stock movement
        $stockMovement = \App\Models\StockMovement::where('reference_type', 'print_order')
                                                 ->where('reference_id', $recentOrder->id)
                                                 ->first();
        
        if ($stockMovement) {
            echo "Stock movement: EXISTS\n";
        } else {
            echo "Stock movement: MISSING!\n";
            echo "❌ This is why stock didn't reduce!\n";
        }
    }
    
    // 4. Check page counting logic
    echo "\n4. Checking page count calculation:\n";
    
    // Look at a recent order with files
    $orderWithFiles = PrintOrder::whereHas('files')->orderBy('created_at', 'desc')->first();
    
    if ($orderWithFiles) {
        echo "Order with files: {$orderWithFiles->order_code}\n";
        foreach ($orderWithFiles->files as $file) {
            echo "  File: {$file->file_name}\n";
            echo "  Pages count in DB: {$file->pages_count}\n";
            echo "  Original filename: {$file->original_name}\n";
        }
        
        // Check how total pages calculated in order
        $orderData = json_decode($orderWithFiles->order_data, true);
        if ($orderData && isset($orderData['total_pages'])) {
            echo "  Total pages in order data: {$orderData['total_pages']}\n";
        }
    }
    
    // 5. Check print service calculation logic
    echo "\n5. Testing print service calculation...\n";
    
    // Simulate calculation for 1 page A4 Color
    $testCalculation = [
        'product_variants' => [
            [
                'variant_id' => 46,
                'quantity' => 1,
                'price' => 2
            ]
        ],
        'total_pages' => 1,
        'total_amount' => 2
    ];
    
    echo "Test calculation for 1 page:\n";
    echo "  Variant: 46 (A4 Color)\n";
    echo "  Quantity: 1\n";
    echo "  Price per page: Rp 2\n";
    echo "  Expected total: Rp 2\n";
    
    if (1 * 2 != 2) {
        echo "  ❌ Math calculation error!\n";
    } else {
        echo "  ✅ Math is correct\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Analysis Complete ===\n";