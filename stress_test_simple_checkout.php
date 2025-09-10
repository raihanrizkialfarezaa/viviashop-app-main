<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Auth;

echo "=== FINAL STRESS TEST: SIMPLE PRODUCT CHECKOUT ===\n\n";

try {
    echo "🧪 TESTING MULTIPLE SIMPLE PRODUCTS WITH DIFFERENT SCENARIOS\n\n";
    
    $simpleProducts = Product::where('type', 'simple')
        ->whereHas('productInventory', function($query) {
            $query->where('qty', '>', 0);
        })
        ->with(['productInventory', 'productImages'])
        ->take(3)
        ->get();
    
    if ($simpleProducts->count() < 1) {
        echo "❌ Need at least 1 simple product\n";
        exit;
    }
    
    $user = User::first();
    Auth::login($user);
    
    $scenarios = [
        ['delivery' => 'self', 'payment' => 'toko', 'name' => 'Self Pickup + Store Payment'],
        ['delivery' => 'self', 'payment' => 'manual', 'name' => 'Self Pickup + Manual Transfer'],
        ['delivery' => 'courier', 'payment' => 'toko', 'name' => 'Courier + Store Payment'],
    ];
    
    $successCount = 0;
    $totalTests = count($scenarios);
    
    foreach ($scenarios as $index => $scenario) {
        echo "--- Test " . ($index + 1) . ": {$scenario['name']} ---\n";
        
        // Clear cart
        Cart::destroy();
        
        // Add random simple product
        $product = $simpleProducts->random();
        
        // Use CartController to add (proper way)
        $cartController = new \App\Http\Controllers\Frontend\CartController(
            app(\App\Services\ProductVariantService::class)
        );
        
        $request = new \Illuminate\Http\Request();
        $request->merge([
            'product_id' => $product->id,
            'qty' => 1
        ]);
        
        $cartResponse = $cartController->store($request);
        $cartData = json_decode($cartResponse->getContent(), true);
        
        if ($cartData['status'] !== 'success') {
            echo "❌ Failed to add to cart\n";
            continue;
        }
        
        echo "   ✅ Added {$product->name} to cart\n";
        
        // Prepare checkout request
        $checkoutRequest = new \Illuminate\Http\Request();
        $checkoutRequest->setMethod('POST');
        
        $checkoutData = [
            'name' => $user->name,
            'address1' => 'Test Address 1',
            'address2' => 'Test Address 2', 
            'postcode' => '12345',
            'phone' => '081234567890',
            'email' => $user->email,
            'payment_method' => $scenario['payment'],
            'delivery_method' => $scenario['delivery'],
            'unique_code' => 0,
            'note' => 'Stress test: ' . $scenario['name']
        ];
        
        if ($scenario['delivery'] === 'courier') {
            $checkoutData['province_id'] = 18;
            $checkoutData['shipping_city_id'] = 388;
            $checkoutData['shipping_district_id'] = 3852;
            $checkoutData['shipping_service'] = 'JNE - REG';
        }
        
        $checkoutRequest->merge($checkoutData);
        
        // Perform checkout
        $orderController = new \App\Http\Controllers\Frontend\OrderController();
        
        try {
            $response = $orderController->doCheckout($checkoutRequest);
            
            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                $url = $response->getTargetUrl();
                if (strpos($url, 'orders/received/') !== false) {
                    echo "   ✅ Checkout successful: $url\n";
                    $successCount++;
                } else {
                    echo "   ❌ Unexpected redirect: $url\n";
                }
            } else {
                echo "   ❌ Unexpected response type\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Checkout failed: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    echo "=== STRESS TEST RESULTS ===\n";
    echo "Successful checkouts: $successCount / $totalTests\n";
    echo "Success rate: " . round(($successCount / $totalTests) * 100, 2) . "%\n";
    
    if ($successCount === $totalTests) {
        echo "🎉 ALL TESTS PASSED! Simple product checkout is working perfectly!\n";
    } else {
        echo "⚠️  Some tests failed. Check logs for details.\n";
    }
    
    echo "\n=== RECENT ORDERS CHECK ===\n";
    $recentOrders = Order::where('user_id', $user->id)
        ->where('created_at', '>=', now()->subMinutes(5))
        ->with('orderItems')
        ->orderBy('created_at', 'desc')
        ->get();
    
    echo "Recent orders created in last 5 minutes: " . $recentOrders->count() . "\n";
    foreach ($recentOrders as $order) {
        echo "- Order {$order->code}: {$order->status}, Items: " . $order->orderItems->count() . "\n";
    }
    
    echo "\n=== FINAL VALIDATION ===\n";
    
    // Check if simple products can be ordered
    $simpleOrderItems = \App\Models\OrderItem::whereHas('order', function($query) use ($user) {
        $query->where('user_id', $user->id)
              ->where('created_at', '>=', now()->subMinutes(10));
    })
    ->where('type', 'simple')
    ->count();
    
    echo "Simple product orders in last 10 minutes: $simpleOrderItems\n";
    
    if ($simpleOrderItems > 0) {
        echo "✅ CONFIRMED: Simple products can be ordered successfully!\n";
    } else {
        echo "❌ No simple product orders found\n";
    }
    
    echo "\n🏁 STRESS TEST COMPLETE\n";
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

?>
