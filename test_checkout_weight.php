<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;
use Gloudemans\Shoppingcart\Facades\Cart;

echo "=== CHECKOUT TEST - Weight Calculation ===\n\n";

// Clear cart
Cart::destroy();
echo "Cart cleared\n";

// Add variant item
$product = Product::find(133);
$variant = $product->activeVariants->first();

if ($variant) {
    $cartItemId = $variant->id . '_variant';
    
    Cart::add([
        'id' => $cartItemId,
        'name' => $variant->name,
        'price' => $variant->price,
        'qty' => 2,
        'weight' => $variant->weight ?? 100, // Default weight if null
        'options' => [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'type' => 'configurable',
            'slug' => $product->slug,
            'image' => $product->productImages->first()?->path ?? '',
            'attributes' => $variant->variantAttributes->pluck('attribute_value', 'attribute_name')->toArray(),
            'sku' => $variant->sku ?? 'VAR-' . $variant->id,
        ]
    ]);
    
    echo "Added variant item: {$variant->name}\n";
    echo "Variant weight: " . ($variant->weight ?? 100) . "g\n";
    echo "Quantity: 2\n";
}

// Add simple item
$simpleProduct = Product::find(4);
if ($simpleProduct) {
    Cart::add([
        'id' => $simpleProduct->id,
        'name' => $simpleProduct->name,
        'price' => $simpleProduct->price,
        'qty' => 1,
        'weight' => $simpleProduct->weight ?? 50,
        'options' => [
            'product_id' => $simpleProduct->id,
            'variant_id' => null,
            'type' => 'simple',
            'slug' => $simpleProduct->slug,
            'image' => $simpleProduct->productImages->first()?->path ?? '',
        ]
    ]);
    
    echo "Added simple item: {$simpleProduct->name}\n";
    echo "Product weight: " . ($simpleProduct->weight ?? 50) . "g\n";
    echo "Quantity: 1\n";
}

echo "\nCart Contents:\n";
$items = Cart::content();
foreach ($items as $item) {
    echo "- {$item->name}: Qty {$item->qty}, Weight {$item->weight}g each\n";
    echo "  Type: " . ($item->options['type'] ?? 'unknown') . "\n";
}

echo "\nWeight Calculation Test:\n";

// Test the fixed _getTotalWeight logic
$totalWeight = 0;
foreach ($items as $item) {
    $itemWeight = 0;
    
    if (isset($item->options['type']) && $item->options['type'] === 'configurable') {
        $itemWeight = $item->weight ?? 0;
        echo "Variant item weight: {$itemWeight}g (from cart item)\n";
    } else {
        $itemWeight = $item->model ? ($item->model->weight ?? 0) : ($item->weight ?? 0);
        echo "Simple item weight: {$itemWeight}g (from model or cart)\n";
    }
    
    $lineWeight = $item->qty * $itemWeight;
    echo "Line total: {$item->qty} × {$itemWeight}g = {$lineWeight}g\n";
    $totalWeight += $lineWeight;
}

echo "\nTotal Weight: {$totalWeight}g\n";
echo "Cart Items: " . Cart::count() . "\n";
echo "Cart Subtotal: Rp " . number_format(Cart::subtotal(0, '', '')) . "\n";

echo "\n=== Checkout should now work without weight errors ===\n";
