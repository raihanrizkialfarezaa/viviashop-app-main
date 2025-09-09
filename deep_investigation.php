<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "DEEP INVESTIGATION - PROFIT vs REVENUE ANOMALY\n";
echo "==============================================\n\n";

$testDate = '2025-09-06';
echo "Analyzing date: {$testDate}\n";
echo "Expected: Keuntungan NEVER > Pendapatan\n\n";

$orders = App\Models\Order::where('payment_status', 'paid')
    ->where('grand_total', '>', 0)
    ->whereDate('created_at', $testDate)
    ->with('orderItems.product')
    ->get();

echo "=== RAW ORDER DATA ===\n";
$total_grand_total = 0;
$total_shipping = 0;

foreach($orders as $order) {
    echo "Order #{$order->id}:\n";
    echo "  - Grand Total: Rp " . number_format($order->grand_total) . "\n";
    echo "  - Shipping Cost: Rp " . number_format($order->shipping_cost) . "\n";
    echo "  - Payment Status: {$order->payment_status}\n";
    
    $total_grand_total += $order->grand_total;
    $total_shipping += $order->shipping_cost;
    
    if ($order->orderItems) {
        foreach($order->orderItems as $item) {
            $cost_price = $item->product ? $item->product->harga_beli : 0;
            $selling_price = $item->base_price;
            
            if ($selling_price <= 0 || $selling_price < ($cost_price * 0.1)) {
                $selling_price = $item->product->price;
                echo "  * FALLBACK USED for {$item->product->name}\n";
            }
            
            echo "  - {$item->product->name}: Qty {$item->qty}\n";
            echo "    base_price: Rp " . number_format($item->base_price) . "\n";
            echo "    product_price: Rp " . number_format($item->product->price) . "\n";
            echo "    used_price: Rp " . number_format($selling_price) . "\n";
            echo "    cost_price: Rp " . number_format($cost_price) . "\n";
            echo "    profit_per_item: Rp " . number_format($selling_price - $cost_price) . "\n";
        }
    }
    echo "\n";
}

echo "=== CALCULATION COMPARISON ===\n";

echo "1. WHAT SYSTEM REPORTS:\n";
$controller = new App\Http\Controllers\Frontend\HomepageController();
$reportData = $controller->getReportsData($testDate, $testDate);

foreach($reportData as $item) {
    if (!empty($item['tanggal'])) {
        echo "  - Penjualan: Rp " . number_format($item['penjualan']) . "\n";
        echo "  - Pendapatan: Rp " . number_format($item['pendapatan']) . "\n";
        echo "  - Keuntungan: Rp " . number_format($item['keuntungan']) . "\n";
        echo "  - Net Sales: Rp " . number_format($item['net_sales']) . "\n";
        echo "  - COGS: Rp " . number_format($item['cost_of_goods']) . "\n";
        break;
    }
}

echo "\n2. MANUAL CALCULATION:\n";
echo "  - Total Grand Total: Rp " . number_format($total_grand_total) . "\n";
echo "  - Total Shipping: Rp " . number_format($total_shipping) . "\n";
echo "  - Net Sales: Rp " . number_format($total_grand_total - $total_shipping) . "\n";

$manual_profit = 0;
$manual_cogs = 0;

foreach($orders as $order) {
    if ($order->orderItems) {
        foreach($order->orderItems as $item) {
            if ($item->product) {
                $cost_price = $item->product->harga_beli;
                $selling_price = $item->base_price;
                
                if ($selling_price <= 0 || $selling_price < ($cost_price * 0.1)) {
                    $selling_price = $item->product->price;
                }
                
                $profit_per_item = $selling_price - $cost_price;
                $manual_profit += ($profit_per_item * $item->qty);
                $manual_cogs += ($cost_price * $item->qty);
            }
        }
    }
}

echo "  - Manual COGS: Rp " . number_format($manual_cogs) . "\n";
echo "  - Manual Profit Margin: Rp " . number_format($manual_profit) . "\n";

echo "\n3. BUSINESS LOGIC CHECK:\n";
$net_sales = $total_grand_total - $total_shipping;
echo "  - Net Sales: Rp " . number_format($net_sales) . "\n";
echo "  - Profit Margin: Rp " . number_format($manual_profit) . "\n";
echo "  - Ratio: " . number_format(($manual_profit / $net_sales) * 100, 2) . "%\n";

if ($manual_profit > $net_sales) {
    echo "  ⚠️  CRITICAL ERROR: Profit > Revenue!\n";
    echo "  This is IMPOSSIBLE in business logic!\n";
} elseif ($manual_profit > ($net_sales * 0.8)) {
    echo "  ⚠️  WARNING: Profit margin too high (>80%)\n";
} else {
    echo "  ✅ Profit margin seems reasonable\n";
}

echo "\n4. DETAILED ITEM ANALYSIS:\n";
foreach($orders as $order) {
    if ($order->orderItems) {
        foreach($order->orderItems as $item) {
            if ($item->product) {
                $cost_price = $item->product->harga_beli;
                $base_price = $item->base_price;
                $product_price = $item->product->price;
                
                echo "  Product: {$item->product->name}\n";
                echo "    - base_price in order: Rp " . number_format($base_price) . "\n";
                echo "    - product master price: Rp " . number_format($product_price) . "\n";
                echo "    - cost price: Rp " . number_format($cost_price) . "\n";
                
                if ($base_price > $product_price) {
                    echo "    ⚠️  ORDER PRICE > MASTER PRICE!\n";
                }
                
                if ($base_price <= 0) {
                    echo "    ⚠️  ZERO/NEGATIVE ORDER PRICE!\n";
                }
                
                echo "\n";
            }
        }
    }
}

?>
