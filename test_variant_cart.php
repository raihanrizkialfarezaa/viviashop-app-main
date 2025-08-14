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

echo "=== TESTING ADD TO CART WITH VARIANT ID ===\n\n";

$user = User::first();
if ($user) {
    Auth::login($user);
    echo "✅ User logged in: {$user->email}\n";
} else {
    echo "❌ No user found for testing\n";
    exit;
}

Cart::destroy();
echo "🗑️  Cart cleared\n";

$variant = Product::where('parent_id', 117)->first();
echo "Variant ID: {$variant->id}\n";
echo "Variant Name: {$variant->name}\n";

$controller = new CartController();

$requestData = [
    '_token' => 'test-token',
    'product_id' => $variant->id,
    'qty' => 1
];

$request = new Request($requestData);
$request->setMethod('POST');

echo "\n🔍 Testing add to cart with variant ID {$variant->id}:\n";

try {
    $response = $controller->store($request);
    
    if ($response instanceof \Illuminate\Http\JsonResponse) {
        $data = $response->getData(true);
        echo "Response: " . json_encode($data) . "\n";
        
        if (isset($data['status']) && $data['status'] === 'success') {
            echo "✅ Successfully added to cart\n";
        } else {
            echo "❌ Failed to add to cart: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ Unexpected response type\n";
    }
    
    $cartCount = Cart::content()->count();
    echo "🛒 Cart items count: {$cartCount}\n";
    
    if ($cartCount > 0) {
        echo "Cart contents:\n";
        foreach (Cart::content() as $item) {
            echo "  - {$item->name} (Qty: {$item->qty}, Price: {$item->price})\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETED ===\n";
