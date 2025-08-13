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

echo "Order 102 Details:\n";
echo "Code: " . $order->code . "\n";
echo "Status: " . $order->status . "\n";
echo "Payment Status: " . $order->payment_status . "\n";
echo "Shipping Service Name: " . $order->shipping_service_name . "\n";
echo "Needs Shipment: " . ($order->needsShipment() ? 'Yes' : 'No') . "\n";
echo "Is Paid: " . ($order->isPaid() ? 'Yes' : 'No') . "\n";
echo "Is Confirmed: " . ($order->isConfirmed() ? 'Yes' : 'No') . "\n";
echo "Is Completed: " . ($order->isCompleted() ? 'Yes' : 'No') . "\n";

if ($order->shipment) {
    echo "Shipment Status: " . $order->shipment->status . "\n";
    echo "Shipment Name: " . $order->shipment->name . "\n";
}

echo "\nExpected behavior for self pickup:\n";
echo "- Should show 'Customer Sudah Ambil Barang?' button if paid and confirmed but not completed\n";
echo "- Should NOT auto-complete after payment\n";
