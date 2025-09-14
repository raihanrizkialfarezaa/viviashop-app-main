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

echo "=== TESTING FIXED CHECKOUT FLOW ===\n\n";

$testUser = User::where('email', 'test@example.com')->first();
auth()->login($testUser);

$product = Product::first();

// Clear and recreate cart with proper model association
Cart::destroy();

echo "1. ADDING PRODUCT TO CART WITH MODEL ASSOCIATION:\n";
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
echo "✓ Cart item added\n";
echo "  Model: " . ($cartItem->model ? get_class($cartItem->model) : 'NULL') . "\n";
echo "  Product ID: " . ($cartItem->model ? $cartItem->model->id : 'N/A') . "\n";
echo "  SKU: " . ($cartItem->model ? $cartItem->model->sku : $cartItem->options['sku'] ?? 'N/A') . "\n";

echo "\n2. TESTING CONTROLLER FLOW:\n";

// Create mock request
$request = new Request();
$request->merge([
    'name' => 'Test User',
    'address1' => 'Test Address 1',
    'address2' => 'Test Address 2',
    'postcode' => '12345',
    'phone' => '08123456789',
    'email' => 'test@example.com',
    'delivery_method' => 'self',
    'payment_method' => 'manual',
    'unique_code' => 123,
    'note' => 'Test order with fixed cart'
]);

$controller = new \App\Http\Controllers\Frontend\OrderController();

try {
    $response = $controller->doCheckout($request);
    
    echo "✓ doCheckout executed successfully\n";
    echo "Response type: " . get_class($response) . "\n";
    
    if ($response instanceof \Illuminate\Http\RedirectResponse) {
        echo "Redirect URL: " . $response->getTargetUrl() . "\n";
        
        if (strpos($response->getTargetUrl(), 'orders/received/') !== false) {
            echo "✅ SUCCESS: Redirecting to correct orders/received page!\n";
        } else {
            echo "❌ Still redirecting to wrong URL\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Error in doCheckout: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

// Clean up
Cart::destroy();
auth()->logout();

echo "\n=== TEST COMPLETE ===\n";