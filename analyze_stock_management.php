<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::create('/', 'GET'));

echo "📊 STOCK MANAGEMENT ANALYSIS - SMART PRINT SYSTEM\n";
echo "================================================\n\n";

echo "1. Current Stock Status for Print Products\n";
echo "==========================================\n";

$printProducts = \App\Models\Product::where('is_print_service', true)->get();

foreach ($printProducts as $product) {
    echo "📄 Product: {$product->name}\n";
    echo "   ID: {$product->id}\n";
    
    $variants = $product->activeVariants;
    if ($variants->count() > 0) {
        foreach ($variants as $variant) {
            $lowStock = $variant->stock <= $variant->min_stock_threshold ? " ⚠️ LOW STOCK" : "";
            echo "   📦 Variant: {$variant->name}\n";
            echo "      Stock: {$variant->stock} units{$lowStock}\n";
            echo "      Min Threshold: {$variant->min_stock_threshold} units\n";
            echo "      Price: Rp " . number_format($variant->price) . "\n";
            echo "      Paper Size: {$variant->paper_size}\n";
            echo "      Print Type: {$variant->print_type}\n\n";
        }
    } else {
        echo "   ❌ No variants found\n\n";
    }
}

echo "2. Checking PrintOrder Records\n";
echo "==============================\n";

$printOrders = \App\Models\PrintOrder::with(['paperVariant', 'paperProduct'])
    ->latest()
    ->take(10)
    ->get();

echo "Recent Print Orders (Last 10):\n";
foreach ($printOrders as $order) {
    echo "📋 Order: {$order->order_code}\n";
    echo "   Status: {$order->status}\n";
    echo "   Pages: {$order->total_pages}\n";
    echo "   Quantity: {$order->quantity}\n";
    echo "   Variant: " . ($order->paperVariant ? $order->paperVariant->name : 'N/A') . "\n";
    echo "   Current Variant Stock: " . ($order->paperVariant ? $order->paperVariant->stock : 'N/A') . "\n";
    echo "   ⚠️ Stock Reduced? NO - Stock management not implemented\n\n";
}

echo "3. Stock Management Issues Identified\n";
echo "=====================================\n";

echo "❌ CRITICAL ISSUES:\n";
echo "1. No stock reduction when print orders are created\n";
echo "2. No stock validation before accepting orders\n";
echo "3. No stock restoration when orders are cancelled\n";
echo "4. No low stock alerts to admin\n";
echo "5. No out-of-stock prevention in frontend\n\n";

echo "📋 REQUIRED IMPLEMENTATIONS:\n";
echo "1. Stock reduction when order status changes to 'confirmed'\n";
echo "2. Stock validation during order creation\n";
echo "3. Stock restoration for cancelled orders\n";
echo "4. Low stock notifications\n";
echo "5. Admin stock management interface\n";
echo "6. Stock reports and analytics\n\n";

echo "4. Paper Consumption Analysis\n";
echo "=============================\n";

$totalOrdersToday = \App\Models\PrintOrder::whereDate('created_at', today())->count();
$totalPagesToday = \App\Models\PrintOrder::whereDate('created_at', today())->sum('total_pages');

echo "Today's Statistics:\n";
echo "📊 Total Orders: {$totalOrdersToday}\n";
echo "📄 Total Pages Printed: {$totalPagesToday}\n";

if ($totalPagesToday > 0) {
    echo "\n📈 Stock Impact Analysis:\n";
    foreach ($printProducts as $product) {
        foreach ($product->activeVariants as $variant) {
            $ordersWithThisVariant = \App\Models\PrintOrder::where('paper_variant_id', $variant->id)
                ->whereDate('created_at', today())
                ->sum('total_pages');
            
            if ($ordersWithThisVariant > 0) {
                $stockUsed = $ordersWithThisVariant;
                $remainingStock = $variant->stock - $stockUsed;
                $percentageUsed = ($stockUsed / $variant->stock) * 100;
                
                echo "   📦 {$variant->name}:\n";
                echo "      Pages Used Today: {$stockUsed}\n";
                echo "      Current Stock: {$variant->stock}\n";
                echo "      Should Be: {$remainingStock}\n";
                echo "      Usage: " . number_format($percentageUsed, 2) . "%\n\n";
            }
        }
    }
}

echo "5. Proposed Stock Management Flow\n";
echo "=================================\n";

echo "🔄 Order Lifecycle with Stock Management:\n";
echo "1. Customer creates order → Check stock availability\n";
echo "2. Order confirmed → Reduce stock immediately\n";
echo "3. Order cancelled → Restore stock\n";
echo "4. Low stock reached → Alert admin\n";
echo "5. Out of stock → Hide variant from selection\n\n";

echo "📱 Admin Management Features Needed:\n";
echo "1. Stock adjustment interface\n";
echo "2. Stock history tracking\n";
echo "3. Low stock alerts dashboard\n";
echo "4. Bulk stock updates\n";
echo "5. Stock reports\n\n";

echo "🎯 IMPLEMENTATION PRIORITY:\n";
echo "HIGH: Stock reduction on order confirmation\n";
echo "HIGH: Stock validation during order creation\n";
echo "MEDIUM: Admin stock management interface\n";
echo "MEDIUM: Low stock alerts\n";
echo "LOW: Advanced analytics and reporting\n";
?>
