<?php

require_once './vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use App\Http\Controllers\Frontend\OrderController;
use Illuminate\Http\Request;
use Gloudemans\Shoppingcart\Facades\Cart;

$app = require_once './bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXED CHECKOUT FLOW TEST ===\n\n";

try {
    echo "1. SETUP TEST ENVIRONMENT:\n";
    
    $testUser = User::where('email', 'test@example.com')->first();
    if (!$testUser) {
        $testUser = User::create([
            'name' => 'Test User',
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
    echo "✓ Test user logged in\n";
    
    $product = Product::first();
    echo "✓ Product found: {$product->name}\n";
    
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
    
    $cartItem = Cart::content()->first();
    echo "✓ Cart item created with model: " . ($cartItem->model ? 'YES' : 'NO') . "\n";
    echo "Cart count: " . Cart::count() . "\n";
    
    echo "\n2. SIMULATE FIXED CHECKOUT:\n";
    
    $requestData = [
        '_token' => csrf_token(),
        'name' => 'Test User Complete',
        'address1' => 'Test Address 1',
        'address2' => 'Test Address 2',
        'postcode' => '12345',
        'phone' => '08123456789',
        'email' => 'test@example.com',
        'delivery_method' => 'self',
        'payment_method' => 'manual',  // Direct Bank Transfer
        'unique_code' => '123',
        'total_amount' => '10123',
        'note' => 'Test order - no payment slip upload in checkout'
    ];
    
    // Importantly, NO payment_slip file in the request
    echo "Request data (no payment_slip):\n";
    foreach($requestData as $key => $value) {
        if ($key !== '_token') {
            echo "  $key: $value\n";
        }
    }
    
    echo "\n3. EXECUTE CHECKOUT:\n";
    
    $request = new Request();
    $request->merge($requestData);
    $request->setMethod('POST');
    
    $controller = new OrderController();
    
    DB::beginTransaction();
    
    try {
        echo "Calling doCheckout method...\n";
        $response = $controller->doCheckout($request);
        
        echo "✅ doCheckout executed successfully\n";
        echo "Response type: " . get_class($response) . "\n";
        
        if (method_exists($response, 'getTargetUrl')) {
            $redirectUrl = $response->getTargetUrl();
            echo "Redirect URL: $redirectUrl\n";
            
            if (strpos($redirectUrl, 'orders/received/') !== false) {
                echo "✅ Correct redirect to order received page\n";
                
                $orderId = str_replace(url('orders/received/'), '', $redirectUrl);
                echo "Order ID from URL: $orderId\n";
                
                if (is_numeric($orderId)) {
                    $order = \App\Models\Order::find($orderId);
                    if ($order) {
                        echo "✅ Order created successfully\n";
                        echo "  Order Code: {$order->code}\n";
                        echo "  Payment Method: {$order->payment_method}\n";
                        echo "  Payment Status: {$order->payment_status}\n";
                        echo "  Total: {$order->grand_total}\n";
                        echo "  Payment Slip: " . ($order->payment_slip ? 'HAS SLIP' : 'NO SLIP') . "\n";
                        
                        if ($order->payment_method === 'manual' && !$order->payment_slip) {
                            echo "✅ Correct: Manual payment with NO payment slip in order\n";
                            echo "User should upload payment slip in next page\n";
                        }
                        
                        $orderItems = $order->orderItems;
                        echo "✅ Order items created: " . $orderItems->count() . "\n";
                        
                        foreach($orderItems as $item) {
                            echo "  Item: {$item->name}\n";
                            echo "  SKU: {$item->sku}\n";
                            echo "  Type: {$item->type}\n";
                            echo "  Qty: {$item->qty}\n";
                        }
                    }
                }
            } else {
                echo "❌ Wrong redirect URL, expected orders/received/\n";
            }
        }
        
        DB::rollBack();
        echo "\n✓ Transaction rolled back (test mode)\n";
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "❌ Checkout failed with error:\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
        
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            echo "\nValidation errors:\n";
            foreach($e->errors() as $field => $errors) {
                echo "  $field: " . implode(', ', $errors) . "\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Test setup failed: " . $e->getMessage() . "\n";
} finally {
    Cart::destroy();
    auth()->logout();
}

echo "\n=== FLOW VALIDATION ===\n";
echo "✅ Direct Bank Transfer flow should be:\n";
echo "1. Checkout page (NO payment slip upload)\n";
echo "2. Place Order button submits to doCheckout\n";
echo "3. Order created with payment_method='manual'\n";
echo "4. Redirect to orders/received/{id}\n";
echo "5. orders/received shows 'Proceed to payment' button\n";
echo "6. Button links to orders/confirmPayment/{id}\n";
echo "7. Confirmation page has payment slip upload\n";
echo "8. User uploads payment slip\n";
echo "9. Admin confirms payment\n";

echo "\n=== TEST COMPLETE ===\n";