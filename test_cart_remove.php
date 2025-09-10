<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Gloudemans\Shoppingcart\Facades\Cart;
use App\Models\Product;

echo "=== TESTING CART REMOVE FUNCTIONALITY ===\n";

Cart::destroy();

echo "1. Adding test product to cart...\n";
$product = Product::find(3);
if ($product) {
    Cart::add([
        'id' => $product->id,
        'name' => $product->name,
        'price' => $product->price,
        'qty' => 1,
        'weight' => $product->weight ?? 50,
        'options' => [
            'product_id' => $product->id,
            'variant_id' => null,
            'type' => 'simple',
            'slug' => $product->slug,
            'image' => $product->productImages->first()?->path ?? '',
        ]
    ]);
    echo "✓ Product added to cart\n";
    echo "Cart items: " . Cart::content()->count() . "\n";
}

echo "\n2. Getting cart item ID for remove test...\n";
$items = Cart::content();
if ($items->count() > 0) {
    $firstItem = $items->first();
    $cartItemId = $firstItem->rowId;
    echo "Cart Item ID: " . $cartItemId . "\n";
    
    echo "\n3. Testing cart remove URL...\n";
    $removeUrl = "http://127.0.0.1:8000/carts/remove/{$cartItemId}";
    echo "Remove URL: " . $removeUrl . "\n";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'follow_location' => false // Don't follow redirects to check response type
        ]
    ]);
    
    $response = @file_get_contents($removeUrl, false, $context);
    
    if (isset($http_response_header)) {
        echo "Response headers:\n";
        foreach ($http_response_header as $header) {
            if (strpos($header, 'HTTP/') === 0 || strpos($header, 'Location:') === 0) {
                echo "  " . $header . "\n";
            }
        }
        
        $isRedirect = false;
        foreach ($http_response_header as $header) {
            if (strpos($header, 'Location:') === 0) {
                $isRedirect = true;
                echo "✓ Response is a redirect (correct behavior)\n";
                break;
            }
        }
        
        if (!$isRedirect && $response) {
            if (strpos($response, '"status":"success"') !== false) {
                echo "✗ Response is JSON (incorrect - should be redirect)\n";
            } else {
                echo "✓ Response is not JSON\n";
            }
        }
    }
    
    echo "\n4. Checking cart after remove...\n";
    $remainingItems = Cart::content()->count();
    echo "Remaining cart items: " . $remainingItems . "\n";
    
    if ($remainingItems == 0) {
        echo "✓ Item successfully removed from cart\n";
    } else {
        echo "⚠️  Item may not have been removed\n";
    }
    
} else {
    echo "✗ No items in cart to test remove\n";
}

echo "\n5. Cleanup...\n";
Cart::destroy();
echo "✓ Cart cleared\n";

echo "\nTest completed.\n";
