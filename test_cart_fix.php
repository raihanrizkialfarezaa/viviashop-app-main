<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Gloudemans\Shoppingcart\Facades\Cart;
use App\Models\Product;

echo "=== CART FUNCTIONALITY TEST ===\n";

Cart::destroy();

echo "1. Adding simple product to cart...\n";
$product3 = Product::find(3);
if ($product3) {
    Cart::add([
        'id' => $product3->id,
        'name' => $product3->name,
        'price' => $product3->price,
        'qty' => 1,
        'weight' => $product3->weight ?? 50,
        'options' => [
            'product_id' => $product3->id,
            'variant_id' => null,
            'type' => 'simple',
            'slug' => $product3->slug,
            'image' => $product3->productImages->first()?->path ?? '',
        ]
    ]);
    echo "✓ Simple product added successfully\n";
} else {
    echo "✗ Product 3 not found\n";
}

echo "\n2. Testing cart view logic simulation...\n";
$items = Cart::content();

foreach ($items as $item) {
    echo "Processing cart item: " . $item->name . "\n";
    
    try {
        if (isset($item->options['type']) && $item->options['type'] === 'configurable') {
            echo "  Type: Configurable\n";
            $product = \App\Models\Product::find($item->options['product_id']);
            $image = !empty($item->options['image']) ? asset('storage/' . $item->options['image']) : asset('themes/ezone/assets/img/cart/3.jpg');
            $maxQty = \App\Models\ProductVariant::find($item->options['variant_id'])->stock ?? 1;
            $displayName = $item->name;
        } else {
            echo "  Type: Simple\n";
            $product = \App\Models\Product::find($item->options['product_id']);
            $image = !empty($product && $product->productImages->first()) ? asset('storage/'.$product->productImages->first()->path) : asset('themes/ezone/assets/img/cart/3.jpg');
            $maxQty = $product && $product->productInventory ? $product->productInventory->qty : 1;
            $displayName = $product ? $product->name : $item->name;
        }
        
        echo "  ✓ Product found: " . ($product ? $product->name : 'N/A') . "\n";
        echo "  ✓ Image path: " . (!empty($item->options['image']) ? $item->options['image'] : 'default') . "\n";
        echo "  ✓ Max quantity: " . $maxQty . "\n";
        echo "  ✓ Display name: " . $displayName . "\n";
        echo "  ✓ No errors in view logic\n";
        
    } catch (Exception $e) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n3. Testing cart URL simulation...\n";
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'header' => "User-Agent: Test\r\n"
    ]
]);

$response = @file_get_contents('http://127.0.0.1:8000/carts', false, $context);
if ($response !== false) {
    echo "✓ Cart URL accessible\n";
    
    if (strpos($response, 'productImages') !== false && strpos($response, 'on null') !== false) {
        echo "✗ Still contains 'productImages on null' error\n";
    } else {
        echo "✓ No 'productImages on null' error detected\n";
    }
    
    if (strpos($response, 'ErrorException') !== false) {
        echo "✗ Contains ErrorException\n";
    } else {
        echo "✓ No ErrorException detected\n";
    }
} else {
    echo "✗ Cart URL not accessible\n";
}

Cart::destroy();
echo "\n✓ Cart cleaned up\n";
echo "\nTest completed.\n";
