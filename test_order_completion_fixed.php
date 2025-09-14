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

echo "=== CREATING TEST ORDER WITH FIXED LOGIC ===\n\n";

// Current AMPLOP status
$amplopVariant = ProductVariant::where('product_id', 9)->first();
$currentStock = $amplopVariant->stock;
echo "Current AMPLOP stock: $currentStock\n";

// Create a test order
$order = new Order();
$order->order_number = 'TEST-' . time();
$order->customer_name = 'Test Customer';
$order->customer_email = 'test@example.com';
$order->customer_phone = '08123456789';
$order->address = 'Test Address';
$order->city = 'Test City';
$order->postal_code = '12345';
$order->total_amount = 15000; // 3 units × 5000
$order->payment_method = 'transfer';
$order->payment_status = 'completed';
$order->order_status = 'pending';
$order->save();

echo "Created Order #{$order->id}\n";

// Create order item
$orderItem = new OrderItem();
$orderItem->order_id = $order->id;
$orderItem->product_id = 9; // AMPLOP
$orderItem->variant_id = $amplopVariant->id;
$orderItem->quantity = 3;
$orderItem->price = 5000;
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
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Cleanup
echo "\nCleaning up test order...\n";
$order->delete();
echo "✓ Test completed\n";