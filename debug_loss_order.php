<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "ANALYZING SPECIFIC LOSS ORDER\n";
echo "============================\n\n";

// Check the specific problematic order on 2025-09-06
$orders = App\Models\Order::whereDate('created_at', '2025-09-06')->get();

foreach($orders as $order) {
    echo "Order #{$order->id}:\n";
    echo "- Total Price: Rp " . number_format($order->total_price) . "\n";
    echo "- Shipping Cost: Rp " . number_format($order->cost_courier) . "\n";
    echo "- Net Sales: Rp " . number_format($order->total_price - $order->cost_courier) . "\n";
    
    if ($order->orderDetails && count($order->orderDetails) > 0) {
        echo "- Order Details:\n";
        $totalCost = 0;
        
        foreach($order->orderDetails as $detail) {
            $productCost = $detail->product ? $detail->product->harga_beli : 0;
            $itemCost = $productCost * $detail->qty;
            $totalCost += $itemCost;
            
            echo "  * " . ($detail->product ? $detail->product->name : 'Unknown Product') . "\n";
            echo "    Qty: {$detail->qty}, Price: Rp " . number_format($detail->price) . "\n";
            echo "    Cost per unit: Rp " . number_format($productCost) . "\n";
            echo "    Total cost: Rp " . number_format($itemCost) . "\n";
        }
        
        echo "- Total Cost of Goods: Rp " . number_format($totalCost) . "\n";
        echo "- Profit: Rp " . number_format(($order->total_price - $order->cost_courier) - $totalCost) . "\n\n";
    } else {
        echo "- No order details found\n\n";
    }
}

// Let's also check if there are any unusual order statuses
echo "Order Statuses:\n";
$statuses = App\Models\Order::whereDate('created_at', '2025-09-06')
    ->selectRaw('status, COUNT(*) as count')
    ->groupBy('status')
    ->get();

foreach($statuses as $status) {
    echo "- Status '{$status->status}': {$status->count} orders\n";
}

?>
