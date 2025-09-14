<?php

require_once './vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\Request;
use Gloudemans\Shoppingcart\Facades\Cart;

$app = require_once './bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DEEP CHECKOUT DEBUG ===\n\n";

$testUser = User::where('email', 'test@example.com')->first();
auth()->login($testUser);

$product = Product::first();

Cart::destroy();
Cart::add([
    'id' => $product->id,
    'name' => $product->name,
    'qty' => 1,
    'price' => $product->price ?? 10000,
    'weight' => $product->weight ?? 100,
    'options' => [
        'type' => 'simple',
        'product_id' => $product->id,
        'sku' => $product->sku ?? 'TEST-SKU'
    ]
]);

echo "1. CHECKING CART ITEM STRUCTURE:\n";
$cartItem = Cart::content()->first();
echo "Cart item ID: " . $cartItem->id . "\n";
echo "Cart item name: " . $cartItem->name . "\n";
echo "Cart item model: " . ($cartItem->model ? get_class($cartItem->model) : 'NULL') . "\n";
echo "Cart item options: " . json_encode($cartItem->options->toArray()) . "\n";

if (!$cartItem->model) {
    echo "\n2. FIXING CART ITEM MODEL:\n";
    
    Cart::destroy();
    
    $cartData = [
        'id' => $product->id,
        'name' => $product->name,
        'qty' => 1,
        'price' => $product->price ?? 10000,
        'weight' => $product->weight ?? 100,
        'options' => [
            'type' => 'simple',
            'product_id' => $product->id,
            'sku' => $product->sku ?? 'TEST-SKU',
            'weight' => $product->weight ?? 100
        ]
    ];
    
    // Associate the product model with cart item
    Cart::add($cartData)->associate(Product::class);
    
    $cartItem = Cart::content()->first();
    echo "✓ Cart item re-created\n";
    echo "Model after associate: " . ($cartItem->model ? get_class($cartItem->model) : 'NULL') . "\n";
}

echo "\n3. TESTING ORDER CREATION MANUALLY:\n";

DB::beginTransaction();

try {
    // Manual order creation following exact controller logic
    $params = [
        'name' => 'Test User',
        'address1' => 'Test Address 1',
        'address2' => 'Test Address 2',
        'postcode' => '12345',
        'phone' => '08123456789',
        'email' => 'test@example.com',
        'delivery_method' => 'self',
        'payment_method' => 'manual',
        'unique_code' => 123,
        'note' => 'Test manual order'
    ];
    
    // Step 1: Save order
    echo "Creating order...\n";
    
    $selectedShipping = [
        'service' => 'Self Pickup',
        'cost' => 0,
        'etd' => 'Same Day',
        'courier' => 'SELF'
    ];
    
    $baseTotalPrice = (int)Cart::subtotal(0,'','');
    $shippingCost = 0;
    $unique_code = $params['unique_code'];
    $grandTotal = $baseTotalPrice + $shippingCost + $unique_code;
    
    $orderDate = date('Y-m-d H:i:s');
    $paymentDue = (new \DateTime($orderDate))->modify('+7 day')->format('Y-m-d H:i:s');
    
    $orderParams = [
        'user_id' => auth()->id(),
        'code' => \App\Models\Order::generateCode(),
        'status' => \App\Models\Order::CREATED,
        'order_date' => $orderDate,
        'payment_due' => $paymentDue,
        'payment_status' => \App\Models\Order::UNPAID,
        'base_total_price' => $baseTotalPrice,
        'tax_amount' => 0,
        'tax_percent' => 0,
        'discount_amount' => 0,
        'discount_percent' => 0,
        'shipping_cost' => $shippingCost,
        'grand_total' => $grandTotal,
        'note' => $params['note'],
        'customer_first_name' => $params['name'],
        'customer_last_name' => $params['name'],
        'customer_address1' => $params['address1'],
        'payment_method' => 'manual',
        'customer_address2' => $params['address2'],
        'customer_phone' => $params['phone'],
        'customer_email' => $params['email'],
        'customer_city_id' => auth()->user()->city_id ?? 1,
        'customer_province_id' => auth()->user()->province_id ?? 1,
        'customer_postcode' => $params['postcode'],
        'shipping_courier' => $selectedShipping['courier'],
        'shipping_service_name' => $selectedShipping['service'],
    ];
    
    $order = \App\Models\Order::create($orderParams);
    echo "✓ Order created: {$order->code} (ID: {$order->id})\n";
    
    // Step 2: Save order items
    echo "Creating order items...\n";
    
    $cartItems = Cart::content();
    foreach ($cartItems as $item) {
        echo "Processing cart item: {$item->name}\n";
        
        $itemTaxAmount = 0;
        $itemTaxPercent = 0;
        $itemDiscountAmount = 0;
        $itemDiscountPercent = 0;
        $itemBaseTotal = $item->qty * $item->price;
        $itemSubTotal = $itemBaseTotal + $itemTaxAmount - $itemDiscountAmount;
        
        // Use model or fallback to loading from database
        if ($item->model) {
            $product = $item->model;
            $productId = $item->model->id;
            $sku = $item->model->sku ?? 'NO-SKU';
            $weight = $item->model->weight ?? 0;
            echo "  Using cart model: {$product->name}\n";
        } else {
            $product = \App\Models\Product::find($item->options['product_id'] ?? $item->id);
            $productId = $item->options['product_id'] ?? $item->id;
            $sku = $product ? $product->sku : 'NO-SKU';
            $weight = $product ? $product->weight : 0;
            echo "  Loaded product from DB: {$product->name}\n";
        }
        
        $orderItemParams = [
            'order_id' => $order->id,
            'product_id' => $productId,
            'qty' => $item->qty,
            'base_price' => $item->price,
            'base_total' => $itemBaseTotal,
            'tax_amount' => $itemTaxAmount,
            'tax_percent' => $itemTaxPercent,
            'discount_amount' => $itemDiscountAmount,
            'discount_percent' => $itemDiscountPercent,
            'sub_total' => $itemSubTotal,
            'sku' => $sku ?: 'NO-SKU',
            'type' => $product ? $product->type : 'simple',
            'name' => $item->name,
            'weight' => ($weight ?? 0) / 1000,
            'attributes' => json_encode($item->options->toArray()),
        ];
        
        echo "  OrderItem params:\n";
        foreach(['sku', 'type', 'name', 'weight', 'attributes'] as $field) {
            echo "    $field: " . ($orderItemParams[$field] ?? 'NULL') . "\n";
        }
        
        $orderItem = \App\Models\OrderItem::create($orderItemParams);
        echo "  ✓ OrderItem created (ID: {$orderItem->id})\n";
    }
    
    // Step 3: Save shipment
    echo "Creating shipment...\n";
    
    $shipmentParams = [
        'user_id' => auth()->id(),
        'order_id' => $order->id,
        'first_name' => $params['name'],
        'last_name' => $params['name'],
        'email' => $params['email'],
        'phone' => $params['phone'],
        'address1' => $params['address1'],
        'address2' => $params['address2'],
        'city_id' => auth()->user()->city_id ?? 1,
        'province_id' => auth()->user()->province_id ?? 1,
        'postcode' => $params['postcode']
    ];
    
    $shipment = \App\Models\Shipment::create($shipmentParams);
    echo "✓ Shipment created (ID: {$shipment->id})\n";
    
    echo "\n4. TESTING REDIRECT:\n";
    $redirectUrl = 'orders/received/' . $order->id;
    echo "Should redirect to: $redirectUrl\n";
    echo "Full URL: " . url($redirectUrl) . "\n";
    
    DB::rollBack();
    echo "\n✓ All steps completed successfully\n";
    echo "Issue might be in the controller's error handling or validation\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

Cart::destroy();
auth()->logout();

echo "\n=== DEBUG COMPLETE ===\n";