<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;
use Gloudemans\Shoppingcart\Facades\Cart;

echo "=== CART TEST - Add Variant Item ===\n\n";

Cart::destroy();
echo "Cart cleared\n";

$product = Product::find(133);
if (!$product) {
    echo "Product not found\n";
    exit;
}

echo "Product: {$product->name}\n";

$variants = $product->activeVariants;
if ($variants->count() === 0) {
    echo "No variants found\n";
    exit;
}

$variant = $variants->first();
echo "Selected variant: {$variant->name} (ID: {$variant->id})\n";
echo "Variant price: Rp " . number_format($variant->price) . "\n";
echo "Variant stock: {$variant->stock}\n";

$cartItemId = $variant->id . '_variant';

Cart::add([
    'id' => $cartItemId,
    'name' => $variant->name,
    'price' => $variant->price,
    'qty' => 1,
    'weight' => $variant->weight ?? 0,
    'options' => [
        'product_id' => $product->id,
        'variant_id' => $variant->id,
        'type' => 'configurable',
        'slug' => $product->slug,
        'image' => $product->productImages->first()?->path ?? '',
        'attributes' => $variant->variantAttributes->pluck('attribute_value', 'attribute_name')->toArray(),
    ]
]);

echo "\nItem added to cart!\n";

$items = Cart::content();
echo "Cart items count: " . $items->count() . "\n\n";

foreach ($items as $item) {
    echo "Cart Item:\n";
    echo "- ID: {$item->id}\n";
    echo "- Name: {$item->name}\n";
    echo "- Price: Rp " . number_format($item->price) . "\n";
    echo "- Qty: {$item->qty}\n";
    echo "- Type: " . ($item->options['type'] ?? 'unknown') . "\n";
    
    if (isset($item->options['type']) && $item->options['type'] === 'configurable') {
        echo "- Product ID: " . $item->options['product_id'] . "\n";
        echo "- Variant ID: " . $item->options['variant_id'] . "\n";
        echo "- Image: " . ($item->options['image'] ?? 'none') . "\n";
        echo "- Attributes: " . json_encode($item->options['attributes'] ?? []) . "\n";
        
        // Test template logic
        $testProduct = \App\Models\Product::find($item->options['product_id']);
        $testImage = !empty($item->options['image']) ? asset('storage/' . $item->options['image']) : asset('themes/ezone/assets/img/cart/3.jpg');
        $testMaxQty = \App\Models\ProductVariant::find($item->options['variant_id'])->stock ?? 1;
        
        echo "- Template Test:\n";
        echo "  * Product loaded: " . ($testProduct ? 'YES' : 'NO') . "\n";
        echo "  * Image path: {$testImage}\n";
        echo "  * Max qty: {$testMaxQty}\n";
        
        if (isset($item->options['attributes']) && !empty($item->options['attributes'])) {
            $attributes = [];
            foreach ($item->options['attributes'] as $attr => $value) {
                $attributes[] = $attr . ': ' . $value;
            }
            $displayName = $item->name . ' (' . implode(', ', $attributes) . ')';
            echo "  * Display name: {$displayName}\n";
        }
    }
    
    echo "\n";
}

echo "=== Test completed - Cart should now work ===\n";
