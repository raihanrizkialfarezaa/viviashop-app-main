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

echo "=== DEBUGGING SIMPLE PRODUCT CHECKOUT ISSUE ===\n\n";

try {
    Cart::destroy();
    echo "1. Cart cleared\n";
    
    $simpleProduct = Product::where('type', 'simple')->with(['productInventory', 'productImages'])->first();
    if (!$simpleProduct) {
        echo "❌ No simple product found\n";
        exit;
    }
    
    echo "2. Found simple product: {$simpleProduct->name} (ID: {$simpleProduct->id})\n";
    echo "   Type: {$simpleProduct->type}\n";
    echo "   Price: {$simpleProduct->price}\n";
    echo "   Stock: " . ($simpleProduct->productInventory ? $simpleProduct->productInventory->qty : 'N/A') . "\n";
    
    $user = User::first();
    if (!$user) {
        echo "❌ No user found\n";
        exit;
    }
    
    Auth::login($user);
    echo "3. Logged in as user: {$user->name} (ID: {$user->id})\n";
    
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
    echo "   Cart count: " . Cart::content()->count() . "\n";
    
    $cartItems = Cart::content();
    echo "5. Cart contents:\n";
    foreach ($cartItems as $item) {
        echo "   - ID: {$item->id}\n";
        echo "   - Name: {$item->name}\n";
        echo "   - Price: {$item->price}\n";
        echo "   - Qty: {$item->qty}\n";
        echo "   - Weight: {$item->weight}\n";
        echo "   - Options: " . json_encode($item->options) . "\n";
        echo "   - Model exists: " . ($item->model ? 'Yes' : 'No') . "\n";
        if ($item->model) {
            echo "     - Model ID: {$item->model->id}\n";
            echo "     - Model Name: {$item->model->name}\n";
            echo "     - Model Type: {$item->model->type}\n";
        }
        echo "\n";
    }
    
    echo "6. Testing checkout flow...\n";
    $orderController = new OrderController();
    
    $checkoutRequest = new Request();
    $checkoutRequest->setMethod('POST');
    $checkoutRequest->merge([
        'name' => 'Test User',
        'address1' => 'Test Address 1',
        'address2' => 'Test Address 2',
        'postcode' => '12345',
        'phone' => '081234567890',
        'email' => 'test@example.com',
        'payment_method' => 'toko',
        'delivery_method' => 'self',
        'unique_code' => 0,
        'note' => 'Test checkout for simple product'
    ]);
    
    echo "   Request data prepared:\n";
    echo "   - Name: " . $checkoutRequest->get('name') . "\n";
    echo "   - Payment method: " . $checkoutRequest->get('payment_method') . "\n";
    echo "   - Delivery method: " . $checkoutRequest->get('delivery_method') . "\n";
    echo "   - Unique code: " . $checkoutRequest->get('unique_code') . "\n";
    
    echo "\n7. Simulating doCheckout method call...\n";
    
    DB::beginTransaction();
    try {
        $params = $checkoutRequest->except('_token');
        
        echo "   Testing _saveOrder method...\n";
        $reflection = new ReflectionClass($orderController);
        $saveOrderMethod = $reflection->getMethod('_saveOrder');
        $saveOrderMethod->setAccessible(true);
        
        $order = $saveOrderMethod->invoke($orderController, $params);
        echo "   ✅ Order saved with ID: {$order->id}\n";
        echo "   - Code: {$order->code}\n";
        echo "   - Status: {$order->status}\n";
        echo "   - Grand Total: {$order->grand_total}\n";
        echo "   - Payment Method: {$order->payment_method}\n";
        
        echo "   Testing _saveOrderItems method...\n";
        $saveOrderItemsMethod = $reflection->getMethod('_saveOrderItems');
        $saveOrderItemsMethod->setAccessible(true);
        
        $saveOrderItemsMethod->invoke($orderController, $order);
        echo "   ✅ Order items saved\n";
        
        $orderItems = $order->orderItems;
        echo "   Order items count: " . $orderItems->count() . "\n";
        foreach ($orderItems as $item) {
            echo "   - Product ID: {$item->product_id}\n";
            echo "   - Name: {$item->name}\n";
            echo "   - Qty: {$item->qty}\n";
            echo "   - Base Price: {$item->base_price}\n";
            echo "   - Sub Total: {$item->sub_total}\n";
            echo "   - Type: {$item->type}\n";
            echo "   - SKU: {$item->sku}\n";
            echo "   - Attributes: {$item->attributes}\n";
        }
        
        echo "   Testing _saveShipment method...\n";
        $saveShipmentMethod = $reflection->getMethod('_saveShipment');
        $saveShipmentMethod->setAccessible(true);
        
        $saveShipmentMethod->invoke($orderController, $order, $params);
        echo "   ✅ Shipment saved\n";
        
        DB::rollBack();
        echo "   Transaction rolled back (test mode)\n";
        
    } catch (Exception $e) {
        DB::rollBack();
        echo "   ❌ Error during checkout simulation: " . $e->getMessage() . "\n";
        echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
    
    echo "\n8. Cart status after test:\n";
    echo "   Cart count: " . Cart::content()->count() . "\n";
    
    echo "\n=== POTENTIAL ISSUES ANALYSIS ===\n";
    
    echo "\n1. Simple product model loading:\n";
    $firstItem = Cart::content()->first();
    if ($firstItem) {
        if (!$firstItem->model) {
            echo "   ❌ Item model is null - this could cause issues in _saveOrderItems\n";
            echo "   🔍 Need to check how simple products are loaded from cart\n";
        } else {
            echo "   ✅ Item model loaded correctly\n";
        }
        
        if (!isset($firstItem->options['type'])) {
            echo "   ❌ Item options['type'] not set - defaulting logic may fail\n";
        } else {
            echo "   ✅ Item type: " . $firstItem->options['type'] . "\n";
        }
    }
    
    echo "\n2. Weight calculation:\n";
    $reflection = new ReflectionClass($orderController);
    $getTotalWeightMethod = $reflection->getMethod('_getTotalWeight');
    $getTotalWeightMethod->setAccessible(true);
    $totalWeight = $getTotalWeightMethod->invoke($orderController);
    echo "   Total weight: {$totalWeight}\n";
    
    if ($totalWeight <= 0) {
        echo "   ⚠️  Weight is 0 or negative - this might affect shipping calculations\n";
    }
    
    echo "\n3. Form validation in frontend:\n";
    echo "   🔍 Check if form validation is causing redirect without submission\n";
    echo "   🔍 Check JavaScript validation function handleFormSubmit()\n";
    echo "   🔍 Check if missing required fields cause page refresh\n";
    
    echo "\n4. Unique code handling:\n";
    echo "   Current unique_code: 0\n";
    echo "   🔍 Check if unique_code validation is causing issues\n";
    
    echo "\n=== RECOMMENDATIONS ===\n";
    echo "1. Add detailed logging to doCheckout method\n";
    echo "2. Check frontend form validation and AJAX handling\n";
    echo "3. Verify simple product cart item structure\n";
    echo "4. Test with different payment and delivery methods\n";
    echo "5. Check for JavaScript errors in browser console\n";
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

?>
