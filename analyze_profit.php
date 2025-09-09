<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "PROFIT ANALYSIS REPORT\n";
echo "======================\n\n";

// 1. Check products with problematic cost prices
echo "1. CHECKING PRODUCTS WITH HIGH COST PRICES:\n";
$products = App\Models\Product::whereNotNull('harga_beli')
    ->orderBy('harga_beli', 'desc')
    ->take(10)
    ->get();

foreach($products as $p) {
    $ratio = $p->price > 0 ? ($p->harga_beli / $p->price) * 100 : 0;
    echo "- {$p->name}: Cost Rp " . number_format($p->harga_beli) . 
         ", Price: Rp " . number_format($p->price) . 
         " (Cost ratio: " . number_format($ratio, 1) . "%)\n";
}

// 2. Check for products where cost > price
echo "\n2. PRODUCTS WITH COST > SELLING PRICE:\n";
$problematic = App\Models\Product::whereRaw('harga_beli > price')->get();
echo "Found {$problematic->count()} products with cost higher than selling price:\n";
foreach($problematic as $p) {
    echo "- {$p->name}: Cost Rp " . number_format($p->harga_beli) . 
         " > Price Rp " . number_format($p->price) . "\n";
}

// 3. Check recent orders with losses
echo "\n3. RECENT ORDERS CAUSING LOSSES:\n";
$lossDate = '2025-09-06';
$orders = App\Models\Order::whereDate('created_at', $lossDate)->get();

foreach($orders as $order) {
    $totalCost = 0;
    echo "Order #{$order->id} (Total: Rp " . number_format($order->total_price) . ", Shipping: Rp " . number_format($order->cost_courier) . "):\n";
    
    foreach($order->orderDetails as $detail) {
        $productCost = $detail->product ? $detail->product->harga_beli : 0;
        $itemCost = $productCost * $detail->qty;
        $totalCost += $itemCost;
        
        echo "  - " . ($detail->product ? $detail->product->name : 'Unknown') . 
             " x{$detail->qty} @ Rp " . number_format($detail->price) . 
             " (Cost: Rp " . number_format($productCost) . " x {$detail->qty} = Rp " . number_format($itemCost) . ")\n";
    }
    
    $netSales = $order->total_price - $order->cost_courier;
    $profit = $netSales - $totalCost;
    echo "  Net Sales: Rp " . number_format($netSales) . " - Cost: Rp " . number_format($totalCost) . " = Profit: Rp " . number_format($profit) . "\n\n";
}

// 4. Suggest solutions
echo "4. RECOMMENDATIONS:\n";
echo "- Update products with unrealistic cost prices\n";
echo "- Ensure all products have proper harga_beli values\n";
echo "- Review pricing strategy for products with high cost ratios\n";
echo "- Consider adding validation to prevent cost > price\n";

?>
