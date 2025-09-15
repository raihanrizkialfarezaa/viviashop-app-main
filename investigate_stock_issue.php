<?php

require_once 'vendor/autoload.php';

use App\Models\ProductVariant;
use App\Models\PrintOrder;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Stock Investigation for A4 Color ===\n\n";

try {
    // Find A4 Color variant
    echo "1. Looking for print service variants...\n";
    
    // Get all print service variants
    $variants = ProductVariant::whereHas('product', function($q) {
        $q->where('is_print_service', true);
    })->with('product')->get();
    
    echo "Available print service variants:\n";
    $a4ColorVariant = null;
    
    foreach ($variants as $variant) {
        echo "  - ID: {$variant->id} | {$variant->name} | Stock: {$variant->stock_quantity} | Price: Rp " . number_format($variant->price, 0, ',', '.') . "\n";
        
        // Look for A4 Color variant (price 2 and stock 9998)
        if ($variant->price == 2 && $variant->stock_quantity == 9998) {
            $a4ColorVariant = $variant;
            echo "    → This is likely the A4 Color variant!\n";
        }
    }
    
    if ($a4ColorVariant) {
        echo "✓ Found variant: ID {$a4ColorVariant->id}\n";
        echo "  Name: {$a4ColorVariant->name}\n";
        echo "  Current Stock: {$a4ColorVariant->stock_quantity}\n";
        echo "  Price: Rp " . number_format($a4ColorVariant->price, 0, ',', '.') . "\n\n";
        
        // Check recent orders using this variant
        echo "2. Checking recent orders with this variant...\n";
        $recentOrders = PrintOrder::where('payment_status', 'paid')
                                 ->orderBy('created_at', 'desc')
                                 ->limit(10)
                                 ->get();
        
        echo "Recent paid orders:\n";
        foreach ($recentOrders as $order) {
            echo "  - {$order->order_code} | {$order->customer_name} | {$order->status} | {$order->created_at}\n";
            
            // Check what variants were ordered
            $orderData = json_decode($order->order_data, true);
            if ($orderData && isset($orderData['product_variants'])) {
                foreach ($orderData['product_variants'] as $variant) {
                    if ($variant['variant_id'] == $a4ColorVariant->id) {
                        echo "    → Used A4 Color variant (qty: {$variant['quantity']})\n";
                    }
                }
            }
        }
        
        // Check stock movements
        echo "\n3. Checking stock movements for this variant...\n";
        $stockMovements = StockMovement::where('variant_id', $a4ColorVariant->id)
                                     ->orderBy('created_at', 'desc')
                                     ->limit(10)
                                     ->get();
        
        if ($stockMovements->count() > 0) {
            echo "Recent stock movements:\n";
            foreach ($stockMovements as $movement) {
                echo "  - {$movement->created_at} | {$movement->type} | Qty: {$movement->quantity} | Ref: {$movement->reference_type}:{$movement->reference_id}\n";
            }
        } else {
            echo "❌ NO STOCK MOVEMENTS FOUND!\n";
            echo "This explains why stock is not decreasing.\n";
        }
        
        // Calculate expected stock
        echo "\n4. Stock calculation analysis...\n";
        $totalOut = StockMovement::where('variant_id', $a4ColorVariant->id)
                                ->where('type', 'out')
                                ->sum('quantity');
        
        $totalIn = StockMovement::where('variant_id', $a4ColorVariant->id)
                               ->where('type', 'in')
                               ->sum('quantity');
        
        echo "Total IN movements: {$totalIn}\n";
        echo "Total OUT movements: {$totalOut}\n";
        echo "Current stock: {$a4ColorVariant->stock_quantity}\n";
        echo "Expected stock should be: " . (10000 + $totalIn - $totalOut) . "\n";
        
    } else {
        echo "❌ A4 Color variant not found\n";
        
        // Show all print service variants
        echo "\nAvailable print service variants:\n";
        $variants = ProductVariant::whereHas('product', function($q) {
            $q->where('is_print_service', true);
        })->with('product')->get();
        
        foreach ($variants as $variant) {
            echo "  - ID: {$variant->id} | {$variant->name} | Stock: {$variant->stock_quantity} | Price: {$variant->price}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Investigation Complete ===\n";