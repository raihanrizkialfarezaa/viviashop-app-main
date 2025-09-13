<?php

/**
 * Simple Shipping Adjustment Test via Artisan Tinker
 * 
 * Run this via: php artisan tinker
 * Then copy-paste this code
 */

echo "🔧 SHIPPING COST ADJUSTMENT - SIMPLE TEST\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Find a test order or create one
$order = \App\Models\Order::latest()->first();

if (!$order) {
    echo "❌ No orders found. Please create an order first through the website.\n";
    exit;
}

echo "📦 Testing with Order #{$order->code}\n";
echo "Original shipping cost: Rp" . number_format($order->shipping_cost, 0, ",", ".") . "\n";
echo "Original courier: {$order->shipping_courier}\n\n";

// Test if order can be adjusted
if ($order->status === 'cancelled') {
    echo "⚠️ Order is cancelled. Using a confirmed order for testing...\n";
    $order = \App\Models\Order::where('status', '!=', 'cancelled')->latest()->first();
    if (!$order) {
        echo "❌ No non-cancelled orders found.\n";
        exit;
    }
}

echo "🔧 Testing shipping adjustment...\n";

// Simulate API rate vs field rate scenario
$originalCost = $order->shipping_cost;
$newCost = $originalCost + 3000; // Field rate is typically higher
$newCourier = 'JNE Express (Adjusted)';
$newService = 'JNE YES';
$note = 'API rate vs field rate adjustment - updated to actual courier charges';

// Get admin user
$admin = \App\Models\User::where('role', 'admin')->first() ?? \App\Models\User::first();

echo "Adjusting from Rp" . number_format($originalCost, 0, ",", ".") . " to Rp" . number_format($newCost, 0, ",", ".") . "\n";

// Perform adjustment
$result = $order->adjustShippingCost($newCost, $newCourier, $newService, $note, $admin->id);

if ($result) {
    $order->refresh();
    echo "✅ Shipping adjustment successful!\n\n";
    
    echo "📋 Updated Order Details:\n";
    echo "Current shipping cost: Rp" . number_format($order->shipping_cost, 0, ",", ".") . "\n";
    echo "Original shipping cost: Rp" . number_format($order->original_shipping_cost, 0, ",", ".") . "\n";
    echo "Current courier: {$order->shipping_courier}\n";
    echo "Original courier: {$order->original_shipping_courier}\n";
    echo "Adjustment note: {$order->shipping_adjustment_note}\n";
    echo "Adjusted by: {$order->shippingAdjustedBy->name}\n";
    echo "Adjusted at: {$order->shipping_adjusted_at}\n";
    echo "New grand total: Rp" . number_format($order->grand_total, 0, ",", ".") . "\n\n";
    
    echo "🧪 Testing Helper Methods:\n";
    echo "Is adjusted: " . ($order->isShippingCostAdjusted() ? 'Yes' : 'No') . "\n";
    echo "Has original data: " . ($order->hasOriginalShippingData() ? 'Yes' : 'No') . "\n";
    echo "Cost difference: Rp" . number_format($order->getShippingCostDifference(), 0, ",", ".") . "\n\n";
    
    echo "🎉 Test completed successfully!\n";
    echo "Admin can now adjust shipping costs when API rates differ from field rates.\n";
    
} else {
    echo "❌ Shipping adjustment failed!\n";
    echo "This might be because the order is cancelled or there's a validation error.\n";
}

echo "\n" . str_repeat("=", 50) . "\n";