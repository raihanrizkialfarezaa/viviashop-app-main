<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "DETAILED PROFIT MARGIN ANALYSIS\n";
echo "===============================\n\n";

$testDate = '2025-09-06';
echo "Analyzing orders on: {$testDate}\n\n";

$orders = App\Models\Order::where('payment_status', 'paid')
    ->where('grand_total', '>', 0)
    ->whereDate('created_at', $testDate)
    ->with('orderItems.product')
    ->get();

echo "Found " . $orders->count() . " valid orders\n\n";

$total_profit_margin = 0;
$total_cost_of_goods = 0;
$total_sales = 0;

foreach($orders as $order) {
    echo "Order #{$order->id}:\n";
    echo "- Grand Total: Rp " . number_format($order->grand_total) . "\n";
    echo "- Shipping: Rp " . number_format($order->shipping_cost) . "\n";
    
    $order_profit = 0;
    
    if ($order->orderItems) {
        foreach($order->orderItems as $item) {
            if ($item->product) {
                $cost_price = $item->product->harga_beli ?? 0;
                $selling_price = $item->base_price;
                $qty = $item->qty;
                
                $profit_per_item = $selling_price - $cost_price;
                $total_item_profit = $profit_per_item * $qty;
                $order_profit += $total_item_profit;
                
                echo "  - {$item->product->name}:\n";
                echo "    Qty: {$qty}, Sell: Rp " . number_format($selling_price) . ", Cost: Rp " . number_format($cost_price) . "\n";
                echo "    Margin per item: Rp " . number_format($profit_per_item) . "\n";
                echo "    Total margin: Rp " . number_format($total_item_profit) . "\n";
                
                $total_cost_of_goods += ($cost_price * $qty);
            }
        }
    }
    
    echo "  Order Profit Margin: Rp " . number_format($order_profit) . "\n\n";
    $total_profit_margin += $order_profit;
    $total_sales += $order->grand_total;
}

echo "SUMMARY:\n";
echo "- Total Sales: Rp " . number_format($total_sales) . "\n";
echo "- Total Cost of Goods: Rp " . number_format($total_cost_of_goods) . "\n";
echo "- Total Profit Margin: Rp " . number_format($total_profit_margin) . "\n";

$pengeluaran = App\Models\Pengeluaran::whereDate('created_at', $testDate)->sum('nominal');
echo "- Pengeluaran: Rp " . number_format($pengeluaran) . "\n";
echo "- Net Profit: Rp " . number_format($total_profit_margin - $pengeluaran) . "\n";

?>
