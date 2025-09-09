<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "DATA INTEGRITY ANALYSIS\n";
echo "=======================\n\n";

echo "1. Checking all orders with inconsistent pricing...\n";

$inconsistentOrders = App\Models\Order::where('payment_status', 'paid')
    ->where('grand_total', '>', 0)
    ->with('orderItems.product')
    ->get()
    ->filter(function($order) {
        foreach($order->orderItems as $item) {
            if ($item->product) {
                $basePrice = $item->base_price;
                $productPrice = $item->product->price;
                
                if ($basePrice > 0 && $productPrice > 0) {
                    $ratio = $basePrice / $productPrice;
                    if ($ratio < 0.1 || $ratio > 10) {
                        return true;
                    }
                }
            }
        }
        return false;
    });

echo "Found " . $inconsistentOrders->count() . " orders with inconsistent pricing\n\n";

foreach($inconsistentOrders->take(10) as $order) {
    echo "Order #{$order->id} (Date: {$order->created_at->format('Y-m-d')}):\n";
    echo "  Grand Total: Rp " . number_format($order->grand_total) . "\n";
    
    foreach($order->orderItems as $item) {
        if ($item->product) {
            $ratio = $item->product->price > 0 ? ($item->base_price / $item->product->price) : 0;
            echo "  - {$item->product->name}:\n";
            echo "    Order Price: Rp " . number_format($item->base_price) . "\n";
            echo "    Master Price: Rp " . number_format($item->product->price) . "\n";
            echo "    Ratio: " . number_format($ratio * 100, 1) . "%\n";
        }
    }
    echo "\n";
}

echo "2. Calculating correct profit if we fix inconsistent data...\n";

$testPeriod = ['2025-09-01', '2025-09-09'];
$orders = App\Models\Order::where('payment_status', 'paid')
    ->where('grand_total', '>', 0)
    ->whereBetween('created_at', $testPeriod)
    ->with('orderItems.product')
    ->get();

$scenario1_profit = 0; // Current logic (skip invalid prices)
$scenario2_profit = 0; // Use proportional pricing
$scenario3_profit = 0; // Use master price

foreach($orders as $order) {
    foreach($order->orderItems as $item) {
        if ($item->product && $item->product->harga_beli) {
            $cost = $item->product->harga_beli;
            $basePrice = $item->base_price;
            $masterPrice = $item->product->price;
            $qty = $item->qty;
            
            // Scenario 1: Current logic
            if ($basePrice > 0) {
                $scenario1_profit += (($basePrice - $cost) * $qty);
            }
            
            // Scenario 2: Proportional pricing
            if ($basePrice > 0 && $masterPrice > 0) {
                $proportionalPrice = ($basePrice / $masterPrice) * $masterPrice; // Same as basePrice
                $scenario2_profit += (($proportionalPrice - $cost) * $qty);
            } elseif ($masterPrice > 0) {
                $scenario2_profit += (($masterPrice - $cost) * $qty);
            }
            
            // Scenario 3: Always use master price
            if ($masterPrice > 0) {
                $scenario3_profit += (($masterPrice - $cost) * $qty);
            }
        }
    }
}

$totalSales = $orders->sum('grand_total');

echo "Total Sales Period: Rp " . number_format($totalSales) . "\n";
echo "Scenario 1 (Current): Rp " . number_format($scenario1_profit) . " (" . number_format(($scenario1_profit/$totalSales)*100, 1) . "%)\n";
echo "Scenario 2 (Proportional): Rp " . number_format($scenario2_profit) . " (" . number_format(($scenario2_profit/$totalSales)*100, 1) . "%)\n";
echo "Scenario 3 (Master Price): Rp " . number_format($scenario3_profit) . " (" . number_format(($scenario3_profit/$totalSales)*100, 1) . "%)\n";

echo "\n3. Business Reality Check:\n";
if ($scenario1_profit > $totalSales * 0.5) {
    echo "⚠️ Scenario 1: Profit margin > 50% (suspicious)\n";
}
if ($scenario2_profit > $totalSales * 0.5) {
    echo "⚠️ Scenario 2: Profit margin > 50% (suspicious)\n";
}
if ($scenario3_profit > $totalSales * 0.5) {
    echo "⚠️ Scenario 3: Profit margin > 50% (suspicious)\n";
}

$reasonableScenario = 1;
if (abs($scenario2_profit) < abs($scenario1_profit) && abs($scenario2_profit) < abs($scenario3_profit)) {
    $reasonableScenario = 2;
} elseif (abs($scenario3_profit) < abs($scenario1_profit) && abs($scenario3_profit) < abs($scenario2_profit)) {
    $reasonableScenario = 3;
}

echo "Most reasonable scenario: {$reasonableScenario}\n";

?>
