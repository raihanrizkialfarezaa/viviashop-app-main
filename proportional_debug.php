<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "PROPORTIONAL CALCULATION DEBUG\n";
echo "==============================\n\n";

$testDate = '2025-09-06';
$orders = App\Models\Order::where('payment_status', 'paid')
    ->where('grand_total', '>', 0)
    ->whereDate('created_at', $testDate)
    ->with('orderItems.product')
    ->get();

foreach($orders as $order) {
    echo "Order #{$order->id}:\n";
    echo "  Grand Total: Rp " . number_format($order->grand_total) . "\n";
    echo "  Shipping: Rp " . number_format($order->shipping_cost) . "\n";
    echo "  Net Sales: Rp " . number_format($order->grand_total - $order->shipping_cost) . "\n";
    
    $order_total_base_price = $order->orderItems->sum(function($item) {
        return $item->base_price * $item->qty;
    });
    
    echo "  Total Base Price: Rp " . number_format($order_total_base_price) . "\n";
    
    $order_net_sales = $order->grand_total - $order->shipping_cost;
    
    $total_profit = 0;
    
    foreach($order->orderItems as $item) {
        if ($item->product) {
            $base_price = $item->base_price;
            $qty = $item->qty;
            $cost = $item->product->harga_beli;
            
            if ($base_price > 0 && $order_total_base_price > 0) {
                $item_proportion = ($base_price * $qty) / $order_total_base_price;
                $actual_selling_price = ($order_net_sales * $item_proportion) / $qty;
                $profit_per_item = $actual_selling_price - $cost;
                $total_item_profit = $profit_per_item * $qty;
                $total_profit += $total_item_profit;
                
                echo "  - {$item->product->name}:\n";
                echo "    Base Price: Rp " . number_format($base_price) . "\n";
                echo "    Qty: {$qty}\n";
                echo "    Item Total Base: Rp " . number_format($base_price * $qty) . "\n";
                echo "    Proportion: " . number_format($item_proportion * 100, 2) . "%\n";
                echo "    Actual Selling Price: Rp " . number_format($actual_selling_price) . "\n";
                echo "    Cost: Rp " . number_format($cost) . "\n";
                echo "    Profit per item: Rp " . number_format($profit_per_item) . "\n";
                echo "    Total profit: Rp " . number_format($total_item_profit) . "\n";
            } else {
                echo "  - {$item->product->name}: SKIPPED (base_price = 0)\n";
            }
        }
    }
    
    echo "  ORDER TOTAL PROFIT: Rp " . number_format($total_profit) . "\n";
    echo "  RATIO vs NET SALES: " . number_format(($total_profit / $order_net_sales) * 100, 1) . "%\n\n";
}

echo "BUSINESS LOGIC REALITY CHECK:\n";
echo "If net sales = Rp 3 and profit = Rp 6000+, this means selling price would be much higher than what customer paid.\n";
echo "This is impossible. The issue is corrupt order item data.\n\n";

echo "RECOMMENDED SOLUTION:\n";
echo "When base_price in order items is unrealistic, use a different approach:\n";
echo "1. Calculate total COGS for the order\n";
echo "2. Profit = Net Sales - COGS\n";
echo "3. This gives realistic profit based on actual money received\n";

?>
