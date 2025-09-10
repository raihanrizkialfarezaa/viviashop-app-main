<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Gloudemans\Shoppingcart\Facades\Cart;
use App\Models\Product;

echo "=== ADDING TEST PRODUCT TO CART ===\n";

$product = Product::find(3);
if (!$product) {
    echo "Product 3 not found\n";
    exit;
}

echo "Adding product: " . $product->name . "\n";
echo "Type: " . $product->type . "\n";

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

echo "Product added to cart\n";

$items = Cart::content();
echo "Cart items count: " . $items->count() . "\n";

foreach ($items as $item) {
    echo "\n--- Cart Item Debug ---\n";
    echo "ID: " . $item->id . "\n";
    echo "Name: " . $item->name . "\n";
    echo "Model exists: " . ($item->model ? 'Yes' : 'No') . "\n";
    
    if ($item->model) {
        echo "Model class: " . get_class($item->model) . "\n";
    } else {
        echo "Model is NULL - This will cause the error!\n";
    }
}

echo "\nDone.\n";
