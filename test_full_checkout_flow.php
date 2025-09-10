<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Frontend\OrderController;
use App\Http\Controllers\Frontend\CartController;
use App\Models\Product;
use App\Models\User;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

echo "=== TESTING FULL CHECKOUT FLOW (SIMPLE PRODUCT) ===\n\n";

try {
    Cart::destroy();
    echo "1. Cart cleared\n";
    
    $simpleProduct = Product::where('type', 'simple')->with(['productInventory', 'productImages'])->first();
    if (!$simpleProduct) {
        echo "❌ No simple product found\n";
        exit;
    }
    
    echo "2. Found simple product: {$simpleProduct->name} (ID: {$simpleProduct->id})\n";
    
    $user = User::first();
    Auth::login($user);
    echo "3. Logged in as user: {$user->name}\n";
    
    $cartController = new CartController(app(\App\Services\ProductVariantService::class));
    
    $addRequest = new Request();
    $addRequest->setMethod('POST');
    $addRequest->merge([
        'product_id' => $simpleProduct->id,
        'qty' => 1
    ]);
    $addRequest->headers->set('Accept', 'application/json');
    
    echo "4. Adding simple product to cart...\n";
    $cartResponse = $cartController->store($addRequest);
    $cartData = json_decode($cartResponse->getContent(), true);
    
    if ($cartData['status'] !== 'success') {
        echo "❌ Failed to add to cart: " . $cartData['message'] . "\n";
        exit;
    }
    
    echo "   ✅ Product added to cart successfully\n";
    
    echo "5. Performing full doCheckout test...\n";
    $orderController = new OrderController();
    
    $checkoutRequest = new Request();
    $checkoutRequest->setMethod('POST');
    $checkoutRequest->merge([
        'name' => $user->name, // Use existing user name
        'address1' => 'Test Address 1',
        'address2' => 'Test Address 2',
        'postcode' => '12345',
        'phone' => '081234567890',
        'email' => $user->email, // Use existing user email to avoid constraint
        'payment_method' => 'toko',
        'delivery_method' => 'self',
        'unique_code' => 0,
        'note' => 'Test checkout for simple product - Full Flow'
    ]);
    
    echo "   Testing doCheckout method directly...\n";
    
    try {
        $response = $orderController->doCheckout($checkoutRequest);
        
        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            $redirectUrl = $response->getTargetUrl();
            echo "   ✅ Checkout successful! Redirect to: $redirectUrl\n";
            
            if (strpos($redirectUrl, 'orders/received/') !== false) {
                echo "   ✅ Correctly redirected to order received page\n";
                
                $orderId = basename($redirectUrl);
                echo "   Order ID: $orderId\n";
                
                $order = \App\Models\Order::find($orderId);
                if ($order) {
                    echo "   Order details:\n";
                    echo "   - Code: {$order->code}\n";
                    echo "   - Status: {$order->status}\n";
                    echo "   - Payment Status: {$order->payment_status}\n";
                    echo "   - Total: {$order->grand_total}\n";
                    echo "   - Items count: " . $order->orderItems->count() . "\n";
                } else {
                    echo "   ⚠️  Order not found in database\n";
                }
            } else {
                echo "   ⚠️  Unexpected redirect URL\n";
            }
        } else {
            echo "   ❌ Unexpected response type: " . get_class($response) . "\n";
            if (method_exists($response, 'getContent')) {
                echo "   Response content: " . $response->getContent() . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "   ❌ doCheckout failed: " . $e->getMessage() . "\n";
        echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
    echo "\n6. Cart status after checkout:\n";
    echo "   Cart count: " . Cart::content()->count() . "\n";
    
    echo "\n=== TESTING WITH COURIER DELIVERY ===\n";
    
    // Test with courier delivery
    Cart::destroy();
    $cartController->store($addRequest); // Add product again
    
    $courierRequest = new Request();
    $courierRequest->setMethod('POST');
    $courierRequest->merge([
        'name' => $user->name,
        'address1' => 'Test Address 1',
        'address2' => 'Test Address 2',
        'postcode' => '12345',
        'phone' => '081234567890',
        'email' => $user->email,
        'payment_method' => 'toko',
        'delivery_method' => 'courier',
        'province_id' => 18, // Default province
        'shipping_city_id' => 388, // Default city
        'shipping_district_id' => 3852, // Default district
        'shipping_service' => 'JNE - REG (1-2 days)',
        'unique_code' => 0,
        'note' => 'Test courier delivery'
    ]);
    
    echo "Testing courier delivery checkout...\n";
    
    try {
        $courierResponse = $orderController->doCheckout($courierRequest);
        
        if ($courierResponse instanceof \Illuminate\Http\RedirectResponse) {
            echo "✅ Courier delivery checkout successful!\n";
            echo "Redirect to: " . $courierResponse->getTargetUrl() . "\n";
        } else {
            echo "❌ Courier delivery failed\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Courier delivery error: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== TESTING WITH AUTOMATIC PAYMENT ===\n";
    
    Cart::destroy();
    $cartController->store($addRequest); // Add product again
    
    $autoPaymentRequest = new Request();
    $autoPaymentRequest->setMethod('POST');
    $autoPaymentRequest->merge([
        'name' => $user->name,
        'address1' => 'Test Address 1',
        'address2' => 'Test Address 2',
        'postcode' => '12345',
        'phone' => '081234567890',
        'email' => $user->email,
        'payment_method' => 'automatic',
        'delivery_method' => 'self',
        'unique_code' => 0,
        'note' => 'Test automatic payment'
    ]);
    
    echo "Testing automatic payment checkout...\n";
    
    try {
        $autoResponse = $orderController->doCheckout($autoPaymentRequest);
        
        if ($autoResponse instanceof \Illuminate\Http\RedirectResponse) {
            echo "✅ Automatic payment checkout successful!\n";
            echo "Redirect to: " . $autoResponse->getTargetUrl() . "\n";
        } else {
            echo "❌ Automatic payment failed\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Automatic payment error: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== CONCLUSION ===\n";
    echo "If all tests passed, the backend checkout is working correctly.\n";
    echo "The issue is likely in the frontend JavaScript validation or form submission.\n";
    echo "Check:\n";
    echo "1. Browser console for JavaScript errors\n";
    echo "2. Form validation logic in handleFormSubmit()\n";
    echo "3. Required field validation\n";
    echo "4. Network requests in browser dev tools\n";
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

?>
