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
echo "Needs Shipment: " . ($order->needsShipment() ? 'Yes' : 'No') . "\n";

if ($order->shipment) {
    echo "Shipment Status: " . $order->shipment->status . "\n";
    echo "Shipped By: " . ($order->shipment->shipped_by ?? 'null') . "\n";
}

echo "\n--- Button Visibility Conditions ---\n";

$condition1 = !$order->isCancelled() && $order->isPaid() && $order->isConfirmed() && !$order->needsShipment() && !$order->isCompleted();
echo "Condition 1 (Confirmed & Not Completed Self Pickup): " . ($condition1 ? 'TRUE' : 'FALSE') . "\n";

$condition2 = !$order->isCancelled() && $order->isPaid() && $order->isCompleted() && $order->shipping_service_name == 'Self Pickup';
echo "Condition 2 (Completed Self Pickup): " . ($condition2 ? 'TRUE' : 'FALSE') . "\n";

if ($condition2) {
    $pickupConfirmed = $order->shipment && $order->shipment->status == 'shipped' && $order->shipment->shipped_by;
    echo "  - Pickup Already Confirmed: " . ($pickupConfirmed ? 'YES (Button Hidden)' : 'NO (Button Shown)') . "\n";
}

echo "\nResult: ";
if ($condition1 || ($condition2 && !($order->shipment && $order->shipment->status == 'shipped' && $order->shipment->shipped_by))) {
    echo "Button 'Customer Sudah Ambil Barang?' should be visible\n";
} else {
    echo "Button should be hidden\n";
}
