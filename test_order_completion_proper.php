<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\ProductInventory;

echo "=== CREATING TEST ORDER WITH PROPER STRUCTURE ===\n\n";

// Current AMPLOP status
$amplopVariant = ProductVariant::where('product_id', 9)->first();
$currentStock = $amplopVariant->stock;
echo "Current AMPLOP stock: $currentStock\n";

// Create a test order with correct structure
$order = new Order();
$order->code = 'TEST-' . time();
$order->order_type = 'ecommerce';
$order->status = 'created';
$order->order_date = now();
$order->payment_due = now()->addDays(7);
$order->payment_status = 'paid';
$order->payment_token = 'test-token-' . time();
$order->base_total_price = 15000; // 3 units × 5000
$order->grand_total = 15000;
$order->customer_first_name = 'Test';
$order->customer_last_name = 'Customer';
$order->customer_email = 'test@example.com';
$order->customer_phone = '08123456789';
$order->customer_address1 = 'Test Address';
$order->customer_city_id = '1';
$order->customer_province_id = '1';
$order->customer_postcode = 12345;
$order->shipping_courier = 'SELF';
$order->shipping_service_name = 'SELF';
$order->user_id = 1;
$order->payment_method = 'manual';
$order->save();

echo "Created Order #{$order->id} with code: {$order->code}\n";

// Create order item with correct structure  
$orderItem = new OrderItem();
$orderItem->order_id = $order->id;
$orderItem->product_id = 9; // AMPLOP
$orderItem->variant_id = $amplopVariant->id;
$orderItem->qty = 3; // Use 'qty' not 'quantity'
$orderItem->base_price = 5000;
$orderItem->base_total = 15000;
$orderItem->sub_total = 15000;
$orderItem->sku = $amplopVariant->sku ?? 'AMPLOP-DEFAULT';
$orderItem->type = 'product';
$orderItem->name = 'AMPLOP';
$orderItem->weight = '0.1'; // Required field
$orderItem->attributes = '{}'; // Required field
$orderItem->save();

echo "Added 3 AMPLOP units to order\n";

// Now test the FIXED order completion logic
echo "\nTesting order completion with fixed logic...\n";

// Use the OrderController logic (should use StockService only)
$controller = new \App\Http\Controllers\Admin\OrderController();

try {
    // Call the private method via reflection (for testing)
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('recordOrderStockMovements');
    $method->setAccessible(true);
    
    $method->invoke($controller, $order);
    
    echo "✓ Order completion executed\n";
    
    // Check final stock
    $amplopVariant = $amplopVariant->fresh();
    $newStock = $amplopVariant->stock;
    
    echo "\nStock changes:\n";
    echo "- Before: $currentStock\n";
    echo "- After: $newStock\n";
    echo "- Difference: " . ($currentStock - $newStock) . "\n";
    echo "- Expected difference: 3\n";
    
    if (($currentStock - $newStock) == 3) {
        echo "✓ SUCCESS: Single deduction working in order completion!\n";
    } else {
        echo "✗ ERROR: Still having deduction issues\n";
    }
    
    // Check movement
    $movement = StockMovement::where('variant_id', $amplopVariant->id)
        ->where('reference_id', $order->id)
        ->where('reference_type', 'order')
        ->first();
        
    if ($movement) {
        echo "\nMovement record:\n";
        echo "- Old Stock: " . $movement->old_stock . "\n";
        echo "- New Stock: " . $movement->new_stock . "\n";
        echo "- Quantity: " . $movement->quantity . "\n";
        echo "- Type: " . $movement->type . "\n";
        echo "- Note: " . $movement->note . "\n";
    } else {
        echo "\n✗ No movement record found!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

// Cleanup
echo "\nCleaning up test order...\n";
if (isset($movement)) {
    $movement->delete();
    echo "✓ Movement deleted\n";
    
    // Restore stock
    $amplopVariant->stock = $currentStock;
    $amplopVariant->save();
    
    $inventory = ProductInventory::where('product_id', 9)->first();
    if ($inventory) {
        $inventory->qty = $currentStock;
        $inventory->save();
    }
    echo "✓ Stock restored\n";
}

$order->orderItems()->delete();
$order->delete();
echo "✓ Test order deleted\n";
echo "✓ Test completed\n";