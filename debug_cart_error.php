<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Gloudemans\Shoppingcart\Facades\Cart;
use App\Models\Product;

echo "=== DEBUGGING CART ERROR ===\n";

$items = Cart::content();

echo "Cart items count: " . $items->count() . "\n\n";

foreach ($items as $item) {
    echo "--- Cart Item ---\n";
    echo "ID: " . $item->id . "\n";
    echo "Name: " . $item->name . "\n";
    echo "Type: " . ($item->options['type'] ?? 'unknown') . "\n";
    echo "Product ID: " . ($item->options['product_id'] ?? 'unknown') . "\n";
    echo "Variant ID: " . ($item->options['variant_id'] ?? 'null') . "\n";
    
    echo "Model: ";
    if ($item->model) {
        echo "Available - " . get_class($item->model) . "\n";
        if (method_exists($item->model, 'productImages')) {
            echo "ProductImages relation: Available\n";
            $images = $item->model->productImages;
            echo "Images count: " . $images->count() . "\n";
        } else {
            echo "ProductImages relation: Not available\n";
        }
    } else {
        echo "NULL - This is the problem!\n";
    }
    
    if (isset($item->options['type']) && $item->options['type'] === 'configurable') {
        echo "Processing as configurable product\n";
        $product = Product::find($item->options['product_id']);
        if ($product) {
            echo "Product found: " . $product->name . "\n";
            echo "Product images count: " . $product->productImages->count() . "\n";
        } else {
            echo "Product not found!\n";
        }
    } else {
        echo "Processing as simple product\n";
        echo "Trying to access \$item->model->productImages - this causes the error\n";
    }
    
    echo "\n";
}

echo "Done.\n";
