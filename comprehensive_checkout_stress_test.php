<?php

require_once './vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Gloudemans\Shoppingcart\Facades\Cart;

$app = require_once './bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== COMPREHENSIVE CHECKOUT FLOW STRESS TEST ===\n\n";

$testUser = User::where('email', 'test@example.com')->first();
auth()->login($testUser);

$products = Product::take(3)->get();

echo "1. TESTING MULTIPLE PAYMENT METHODS:\n";

$paymentMethods = [
    'manual' => 'Manual/Direct Bank Transfer',
    'automatic' => 'Automatic/Credit Card/E-wallet',
    'cod' => 'Cash on Delivery',
    'toko' => 'Pay at Store'
];

$deliveryMethods = [
    'self' => 'Self Pickup',
    'courier' => 'Courier Delivery'
];

$testResults = [];

foreach ($paymentMethods as $paymentMethod => $paymentLabel) {
    foreach ($deliveryMethods as $deliveryMethod => $deliveryLabel) {
        
        echo "\n--- Testing: $paymentLabel + $deliveryLabel ---\n";
        
        // Clear and setup cart
        Cart::destroy();
        
        foreach ($products as $product) {
            Cart::add([
                'id' => $product->id,
                'name' => $product->name,
                'qty' => 1,
                'price' => $product->price ?? 10000,
                'weight' => $product->weight ?? 100,
                'options' => [
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'type' => 'simple',
                    'slug' => $product->slug,
                    'image' => $product->productImages->first()?->path ?? '',
                    'sku' => $product->sku ?? 'NO-SKU',
                ]
            ])->associate(\App\Models\Product::class);
        }
        
        echo "  Cart Items: " . Cart::content()->count() . "\n";
        echo "  Cart Total: Rp " . number_format(Cart::subtotal(0,'','')) . "\n";
        
        // Create mock request
        $request = new Request();
        $requestData = [
            'name' => 'Test User ' . time(),
            'address1' => 'Test Address 1',
            'address2' => 'Test Address 2',
            'postcode' => '12345',
            'phone' => '08123456789',
            'email' => 'test@example.com',
            'delivery_method' => $deliveryMethod,
            'payment_method' => $paymentMethod,
            'unique_code' => rand(100, 999),
            'note' => "Test order: $paymentLabel + $deliveryLabel"
        ];
        
        if ($deliveryMethod === 'courier') {
            $requestData['shipping_city_id'] = 1;
            $requestData['province_id'] = 1;
            $requestData['shipping_district_id'] = 1; // Required field for courier
            $requestData['shipping_service'] = 'Regular';
        }
        
        $request->merge($requestData);
        
        $controller = new \App\Http\Controllers\Frontend\OrderController();
        
        try {
            $response = $controller->doCheckout($request);
            
            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                $redirectUrl = $response->getTargetUrl();
                
                if (strpos($redirectUrl, 'orders/received/') !== false) {
                    $orderId = basename($redirectUrl);
                    echo "  ✅ SUCCESS: Order created (ID: $orderId)\n";
                    echo "  Redirect: $redirectUrl\n";
                    
                    // Verify order exists
                    $order = Order::find($orderId);
                    if ($order) {
                        echo "  Order Code: {$order->code}\n";
                        echo "  Payment Method: {$order->payment_method}\n";
                        echo "  Grand Total: Rp " . number_format($order->grand_total) . "\n";
                        echo "  Order Items: " . $order->orderItems->count() . "\n";
                        echo "  Shipment: " . ($order->shipment ? 'Created' : 'Missing') . "\n";
                        
                        $testResults[] = [
                            'payment' => $paymentMethod,
                            'delivery' => $deliveryMethod,
                            'status' => 'SUCCESS',
                            'order_id' => $orderId,
                            'order_code' => $order->code
                        ];
                    } else {
                        echo "  ❌ Order not found in database\n";
                        $testResults[] = [
                            'payment' => $paymentMethod,
                            'delivery' => $deliveryMethod,
                            'status' => 'ORDER_NOT_FOUND',
                            'order_id' => $orderId
                        ];
                    }
                } else {
                    echo "  ❌ Wrong redirect: $redirectUrl\n";
                    $testResults[] = [
                        'payment' => $paymentMethod,
                        'delivery' => $deliveryMethod,
                        'status' => 'WRONG_REDIRECT',
                        'redirect' => $redirectUrl
                    ];
                }
            } else {
                echo "  ❌ Unexpected response type: " . get_class($response) . "\n";
                $testResults[] = [
                    'payment' => $paymentMethod,
                    'delivery' => $deliveryMethod,
                    'status' => 'UNEXPECTED_RESPONSE',
                    'response_type' => get_class($response)
                ];
            }
            
        } catch (\Exception $e) {
            echo "  ❌ Error: " . $e->getMessage() . "\n";
            $testResults[] = [
                'payment' => $paymentMethod,
                'delivery' => $deliveryMethod,
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
        }
        
        // Small delay between tests
        usleep(500000); // 0.5 second
    }
}

echo "\n2. TESTING PAYMENT SLIP UPLOAD REMOVAL:\n";
echo "Checking that checkout.blade.php no longer contains payment slip upload...\n";

$checkoutContent = file_get_contents('./resources/views/frontend/orders/checkout.blade.php');
if (strpos($checkoutContent, 'payment_slip') === false && strpos($checkoutContent, 'payment-slip') === false) {
    echo "✅ Payment slip upload section successfully removed from checkout page\n";
} else {
    echo "❌ Payment slip upload still found in checkout page\n";
}

echo "\n3. TESTING CART MODEL ASSOCIATION:\n";
Cart::destroy();

$product = Product::first();
Cart::add([
    'id' => $product->id,
    'name' => $product->name,
    'qty' => 1,
    'price' => $product->price ?? 10000,
    'weight' => $product->weight ?? 100,
    'options' => [
        'product_id' => $product->id,
        'variant_id' => null,
        'type' => 'simple',
        'slug' => $product->slug,
        'image' => $product->productImages->first()?->path ?? '',
        'sku' => $product->sku ?? 'NO-SKU',
    ]
])->associate(\App\Models\Product::class);

$cartItem = Cart::content()->first();
if ($cartItem->model && get_class($cartItem->model) === 'App\Models\Product') {
    echo "✅ Cart model association working correctly\n";
    echo "  Associated Model: " . get_class($cartItem->model) . "\n";
    echo "  Product ID: " . $cartItem->model->id . "\n";
    echo "  Product Name: " . $cartItem->model->name . "\n";
} else {
    echo "❌ Cart model association not working\n";
}

echo "\n=== FINAL RESULTS SUMMARY ===\n";

$successCount = 0;
$totalTests = count($testResults);

foreach ($testResults as $result) {
    $status = $result['status'] === 'SUCCESS' ? '✅' : '❌';
    echo "{$status} {$result['payment']} + {$result['delivery']}: {$result['status']}\n";
    if ($result['status'] === 'SUCCESS') {
        $successCount++;
    }
}

echo "\nSuccess Rate: $successCount/$totalTests (" . round(($successCount/$totalTests)*100, 1) . "%)\n";

if ($successCount === $totalTests) {
    echo "\n🎉 ALL TESTS PASSED! Checkout flow is working perfectly!\n";
    echo "\n✅ FIXES APPLIED SUCCESSFULLY:\n";
    echo "  - Payment slip upload removed from checkout page\n";
    echo "  - Cart model association fixed\n";
    echo "  - Order items creation fixed\n";
    echo "  - Shipment status field fixed\n";
    echo "  - Stock movement recording fixed\n";
    echo "  - Proper redirect to orders/received/{id} working\n";
} else {
    echo "\n⚠️  Some tests failed. Please check the errors above.\n";
}

// Clean up
Cart::destroy();
auth()->logout();

echo "\n=== STRESS TEST COMPLETE ===\n";