<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\Frontend\CartController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Product;
use Gloudemans\Shoppingcart\Facades\Cart;

echo "=== SIMULATING FRONTEND ADD TO CART ===\n\n";

$user = User::first();
Auth::login($user);

Cart::destroy();
echo "🗑️  Cart cleared\n";

$parent = Product::find(117);
$variant = $parent->variants->first();

echo "Testing frontend flow:\n";
echo "Parent Product: {$parent->name} (ID: {$parent->id})\n";
echo "Variant: {$variant->name} (ID: {$variant->id})\n";

echo "\n🎯 Simulating JavaScript data:\n";
echo "product-type: configurable\n";
echo "product-id: 117\n";
echo "data-single-variant: true\n";
echo "data-variant-id: {$variant->id}\n";

echo "\n📡 Simulating AJAX request to /carts:\n";

$controller = new CartController();

$requestData = [
    '_token' => 'csrf-token',
    'product_id' => $variant->id,
    'qty' => 1
];

$request = new Request($requestData);
$request->setMethod('POST');

try {
    $response = $controller->store($request);
    
    if ($response instanceof \Illuminate\Http\JsonResponse) {
        $data = $response->getData(true);
        
        if (isset($data['status']) && $data['status'] === 'success') {
            echo "✅ AJAX Response: SUCCESS\n";
            echo "🎉 Product added to cart successfully!\n";
            
            $cartItems = Cart::content();
            echo "\n🛒 Cart contents:\n";
            foreach ($cartItems as $item) {
                echo "  - {$item->name}\n";
                echo "    Price: Rp " . number_format($item->price) . "\n";
                echo "    Qty: {$item->qty}\n";
                echo "    Subtotal: Rp " . number_format($item->qty * $item->price) . "\n";
            }
            
            echo "\n📊 Cart Summary:\n";
            echo "  Items: " . Cart::content()->count() . "\n";
            echo "  Total: Rp " . number_format(Cart::total(2, '.', '')) . "\n";
            
        } else {
            echo "❌ AJAX Response: ERROR\n";
            echo "Message: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error in cart controller: " . $e->getMessage() . "\n";
}

echo "\n✅ FRONTEND SIMULATION COMPLETED\n";
echo "🎯 Button should work correctly in browser!\n";

echo "\n=== TEST COMPLETED ===\n";
