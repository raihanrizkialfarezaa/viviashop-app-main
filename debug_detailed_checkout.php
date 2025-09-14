<?php

require_once './vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Log;

$app = require_once './bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DETAILED CHECKOUT ERROR ANALYSIS ===\n\n";

try {
    $testUser = User::where('email', 'test@example.com')->first();
    if (!$testUser) {
        $testUser = User::create([
            'name' => 'Test User Debug',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'phone' => '08123456789',
            'address1' => 'Test Address 1',
            'address2' => 'Test Address 2', 
            'postcode' => '12345',
            'city_id' => 1,
            'province_id' => 1
        ]);
    }
    
    auth()->login($testUser);
    
    $product = Product::first();
    
    Cart::destroy();
    Cart::add([
        'id' => $product->id,
        'name' => $product->name,
        'qty' => 1,
        'price' => $product->price ?? 10000,
        'options' => [
            'type' => 'simple',
            'product_id' => $product->id
        ]
    ]);
    
    echo "✓ Setup complete\n";
    echo "User: {$testUser->name} (ID: {$testUser->id})\n";
    echo "Product: {$product->name} (ID: {$product->id})\n";
    echo "Cart count: " . Cart::count() . "\n";
    
    echo "\n=== TESTING doCheckout STEP BY STEP ===\n";
    
    $params = [
        'name' => 'Test User Debug',
        'address1' => 'Test Address 1',
        'address2' => 'Test Address 2',
        'postcode' => '12345',
        'phone' => '08123456789',
        'email' => 'test@example.com',
        'delivery_method' => 'self',
        'payment_method' => 'manual',
        'unique_code' => 123,
        'total_amount' => 10123,
        'note' => 'Test debug order'
    ];
    
    echo "1. VALIDATION CHECK:\n";
    $validationRules = [
        'name' => 'required|string|max:255',
        'address1' => 'required|string|max:255',
        'address2' => 'nullable|string|max:255',
        'postcode' => 'required|string|max:20',
        'phone' => 'required|string|max:15',
        'email' => 'required|email|max:255',
        'payment_method' => 'required|string|in:manual,automatic,cod,toko',
        'delivery_method' => 'required|string|in:self,courier',
    ];
    
    $validator = \Illuminate\Support\Facades\Validator::make($params, $validationRules);
    
    if ($validator->fails()) {
        echo "❌ Validation failed:\n";
        foreach($validator->errors()->all() as $error) {
            echo "  - $error\n";
        }
        exit;
    }
    
    echo "✓ Validation passed\n";
    
    echo "\n2. CART CHECK:\n";
    if (Cart::count() <= 0) {
        echo "❌ Cart is empty\n";
        exit;
    }
    echo "✓ Cart has items: " . Cart::count() . "\n";
    
    echo "\n3. ORDER CREATION TEST:\n";
    
    DB::beginTransaction();
    
    try {
        
        echo "Creating order with params...\n";
        
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
        
        echo "  Base total: $baseTotalPrice\n";
        echo "  Shipping: $shippingCost\n";
        echo "  Unique code: $unique_code\n";
        echo "  Grand total: $grandTotal\n";
        
        $orderDate = date('Y-m-d H:i:s');
        $paymentDue = (new \DateTime($orderDate))->modify('+7 day')->format('Y-m-d H:i:s');
        
        $orderParams = [
            'user_id' => auth()->id(),
            'code' => Order::generateCode(),
            'status' => Order::CREATED,
            'order_date' => $orderDate,
            'payment_due' => $paymentDue,
            'payment_status' => Order::UNPAID,
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
        
        echo "  Order params prepared\n";
        
        $order = Order::create($orderParams);
        
        echo "✓ Order created successfully\n";
        echo "  Order ID: {$order->id}\n";
        echo "  Order Code: {$order->code}\n";
        
        echo "\n4. ORDER ITEMS TEST:\n";
        
        $cartItems = Cart::content();
        foreach ($cartItems as $item) {
            echo "  Processing item: {$item->name}\n";
            
            $orderItemParams = [
                'order_id' => $order->id,
                'product_id' => $item->options['product_id'] ?? $item->id,
                'qty' => $item->qty,
                'base_price' => $item->price,
                'base_total' => $item->price * $item->qty,
                'product_name' => $item->name,
                'product_sku' => $item->options['sku'] ?? '',
                'product_type' => $item->options['type'] ?? 'simple',
                'product_weight' => $item->options['weight'] ?? 0,
            ];
            
            $orderItem = \App\Models\OrderItem::create($orderItemParams);
            echo "    ✓ Order item created (ID: {$orderItem->id})\n";
        }
        
        echo "\n5. REDIRECT TEST:\n";
        $redirectUrl = 'orders/received/' . $order->id;
        echo "Should redirect to: $redirectUrl\n";
        echo "Full URL would be: " . url($redirectUrl) . "\n";
        
        DB::rollBack();
        echo "\n✓ Transaction rolled back (test mode)\n";
        
        echo "\n=== CONCLUSION ===\n";
        echo "✅ All steps completed successfully\n";
        echo "The issue might be in form submission or JavaScript validation\n";
        echo "Controller logic appears to be working correctly\n";
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "❌ Error in order creation:\n";
        echo "Message: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
        echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Setup error: " . $e->getMessage() . "\n";
} finally {
    Cart::destroy();
    auth()->logout();
}

echo "\n=== TEST COMPLETE ===\n";