<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;

$order = Order::with('shipment')->find(103);

if (!$order) {
    echo "Order 103 not found.\n";
    exit;
}

echo "Order 103 Analysis:\n";
echo "Status: " . $order->status . "\n";
echo "Payment Status: " . $order->payment_status . "\n";
echo "Shipping Service: " . $order->shipping_service_name . "\n";
echo "Is Completed: " . ($order->isCompleted() ? 'Yes' : 'No') . "\n";
echo "Notes: " . $order->notes . "\n";

if ($order->shipment) {
    echo "\nShipment Details:\n";
    echo "Status: " . $order->shipment->status . "\n";
    echo "Shipped By: " . ($order->shipment->shipped_by ?? 'null') . "\n";
    echo "Shipped At: " . ($order->shipment->shipped_at ?? 'null') . "\n";
}

$hasPickupConfirmation = strpos($order->notes, 'Self pickup confirmed by admin') !== false;
echo "\nHas pickup confirmation in notes: " . ($hasPickupConfirmation ? 'Yes' : 'No') . "\n";

$shipmentConfirmed = $order->shipment && $order->shipment->status == 'shipped' && $order->shipment->shipped_by;
echo "Shipment marked as shipped with admin confirmation: " . ($shipmentConfirmed ? 'Yes' : 'No') . "\n";
