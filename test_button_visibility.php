<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;

$order = Order::with('shipment')->find(102);

if (!$order) {
    echo "Order 102 not found.\n";
    exit;
}

echo "Order 102 Button Visibility Test:\n\n";

echo "Order Status: " . $order->status . "\n";
echo "Payment Status: " . $order->payment_status . "\n";
echo "Shipping Service: " . $order->shipping_service_name . "\n";
echo "Is Cancelled: " . ($order->isCancelled() ? 'Yes' : 'No') . "\n";
echo "Is Paid: " . ($order->isPaid() ? 'Yes' : 'No') . "\n";
echo "Is Confirmed: " . ($order->isConfirmed() ? 'Yes' : 'No') . "\n";
echo "Is Completed: " . ($order->isCompleted() ? 'Yes' : 'No') . "\n";
echo "Needs Shipment: " . ($order->needsShipment() ? 'Yes' : 'No') . "\n\n";

// Test condition 1: Normal confirmed self pickup
$condition1 = !$order->isCancelled() && $order->isPaid() && $order->isConfirmed() && !$order->needsShipment() && !$order->isCompleted();
echo "Condition 1 (Confirmed & Not Completed): " . ($condition1 ? 'TRUE - Button should show' : 'FALSE') . "\n";

// Test condition 2: Already completed self pickup  
$condition2 = !$order->isCancelled() && $order->isPaid() && $order->isCompleted() && $order->shipping_service_name == 'Self Pickup';
echo "Condition 2 (Completed Self Pickup): " . ($condition2 ? 'TRUE - Button should show' : 'FALSE') . "\n";

echo "\nResult: ";
if ($condition1 || $condition2) {
    echo "Button 'Customer Sudah Ambil Barang?' should be visible\n";
} else {
    echo "No pickup confirmation button should show\n";
}
