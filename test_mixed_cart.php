<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use Gloudemans\Shoppingcart\Facades\Cart;

echo "=== CART TEST - Mixed Items ===\n\n";

// Add simple product
$simpleProduct = Product::find(4);
if ($simpleProduct) {
    echo "Adding simple product: {$simpleProduct->name}\n";
    
    Cart::add([
        'id' => $simpleProduct->id,
        'name' => $simpleProduct->name,
        'price' => $simpleProduct->price,
        'qty' => 2,
        'weight' => $simpleProduct->weight ?? 0,
        'options' => [
            'product_id' => $simpleProduct->id,
            'variant_id' => null,
            'type' => 'simple',
            'slug' => $simpleProduct->slug,
            'image' => $simpleProduct->productImages->first()?->path ?? '',
        ]
    ]);
    
    echo "Simple product added!\n\n";
}

echo "Final cart contents:\n";
$items = Cart::content();
echo "Total items: " . $items->count() . "\n\n";

foreach ($items as $item) {
    echo "Item: {$item->name}\n";
    echo "- Type: " . ($item->options['type'] ?? 'unknown') . "\n";
    echo "- Price: Rp " . number_format($item->price) . "\n";
    echo "- Qty: {$item->qty}\n";
    echo "- Total: Rp " . number_format($item->price * $item->qty) . "\n";
    
    if (isset($item->options['attributes']) && !empty($item->options['attributes'])) {
        echo "- Attributes: " . json_encode($item->options['attributes']) . "\n";
    }
    echo "\n";
}

echo "Cart subtotal: Rp " . number_format(Cart::subtotal(0, '', '')) . "\n";
echo "Cart total: Rp " . number_format(Cart::total(0, '', '')) . "\n";

echo "\n=== Mixed cart test completed ===\n";
