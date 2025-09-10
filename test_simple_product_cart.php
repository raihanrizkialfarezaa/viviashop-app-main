<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Product;
use App\Models\User;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Auth;

echo "=== TESTING SIMPLE PRODUCT CART & CHECKOUT AVAILABILITY ===\n\n";

try {
    // Clear cart
    Cart::destroy();
    
    // Find a simple product
    $simpleProduct = Product::where('type', 'simple')
        ->whereHas('productInventory', function($query) {
            $query->where('qty', '>', 0);
        })
        ->with(['productInventory', 'productImages'])
        ->first();
    
    if (!$simpleProduct) {
        echo "❌ No simple product with inventory found\n";
        
        // Let's check all simple products
        $simpleProducts = Product::where('type', 'simple')->with('productInventory')->get();
        echo "Available simple products:\n";
        foreach ($simpleProducts as $product) {
            $stock = $product->productInventory ? $product->productInventory->qty : 0;
            echo "- ID: {$product->id}, Name: {$product->name}, Stock: {$stock}\n";
        }
        exit;
    }
    
    echo "✅ Found simple product with stock:\n";
    echo "   ID: {$simpleProduct->id}\n";
    echo "   Name: {$simpleProduct->name}\n";
    echo "   Type: {$simpleProduct->type}\n";
    echo "   Price: {$simpleProduct->price}\n";
    echo "   Stock: " . ($simpleProduct->productInventory ? $simpleProduct->productInventory->qty : 0) . "\n";
    echo "   Weight: " . ($simpleProduct->weight ?? 'NULL') . "\n";
    echo "   SKU: " . ($simpleProduct->sku ?? 'NULL') . "\n";
    
    // Login user
    $user = User::first();
    if (!$user) {
        echo "❌ No user found\n";
        exit;
    }
    
    Auth::login($user);
    echo "\n✅ Logged in as: {$user->name} (ID: {$user->id})\n";
    echo "   Email: {$user->email}\n";
    echo "   Province ID: " . ($user->province_id ?? 'NULL') . "\n";
    echo "   City ID: " . ($user->city_id ?? 'NULL') . "\n";
    
    // Add to cart using the actual Cart facade
    Cart::add([
        'id' => $simpleProduct->id,
        'name' => $simpleProduct->name,
        'price' => $simpleProduct->price,
        'qty' => 1,
        'weight' => $simpleProduct->weight ?? 100,
        'options' => [
            'product_id' => $simpleProduct->id,
            'variant_id' => null,
            'type' => 'simple',
            'slug' => $simpleProduct->slug,
            'image' => $simpleProduct->productImages->first()?->path ?? '',
        ]
    ], $simpleProduct);
    
    echo "\n✅ Product added to cart\n";
    echo "   Cart count: " . Cart::content()->count() . "\n";
    echo "   Cart subtotal: " . Cart::subtotal() . "\n";
    
    $cartItem = Cart::content()->first();
    echo "\n📦 Cart item details:\n";
    echo "   ID: {$cartItem->id}\n";
    echo "   Name: {$cartItem->name}\n";
    echo "   Price: {$cartItem->price}\n";
    echo "   Qty: {$cartItem->qty}\n";
    echo "   Weight: {$cartItem->weight}\n";
    echo "   Model exists: " . ($cartItem->model ? 'Yes' : 'No') . "\n";
    if ($cartItem->model) {
        echo "   Model ID: {$cartItem->model->id}\n";
        echo "   Model Type: {$cartItem->model->type}\n";
        echo "   Model Weight: " . ($cartItem->model->weight ?? 'NULL') . "\n";
    }
    echo "   Options: " . json_encode($cartItem->options) . "\n";
    
    echo "\n=== CHECKOUT PAGE ACCESS TEST ===\n";
    
    // Test accessing checkout page
    $checkoutUrl = url('orders/checkout');
    echo "Checkout URL: {$checkoutUrl}\n";
    
    // Check if cart has items (required for checkout)
    if (Cart::count() == 0) {
        echo "❌ Cart is empty - will redirect to cart page\n";
    } else {
        echo "✅ Cart has items - checkout page should be accessible\n";
    }
    
    echo "\n=== SIMPLE PRODUCT DETECTION ===\n";
    $cartItems = Cart::content();
    $hasSimpleProduct = false;
    $hasConfigurableProduct = false;
    
    foreach ($cartItems as $item) {
        if (isset($item->options['type'])) {
            if ($item->options['type'] === 'simple') {
                $hasSimpleProduct = true;
                echo "✅ Found simple product in cart: {$item->name}\n";
            } elseif ($item->options['type'] === 'configurable') {
                $hasConfigurableProduct = true;
                echo "✅ Found configurable product in cart: {$item->name}\n";
            }
        } else {
            echo "⚠️  Product in cart without type option: {$item->name}\n";
        }
    }
    
    if ($hasSimpleProduct && !$hasConfigurableProduct) {
        echo "\n🎯 CART CONTAINS ONLY SIMPLE PRODUCTS\n";
        echo "This is the scenario that's causing the checkout issue!\n";
    }
    
    echo "\n=== TESTING DIFFERENT SCENARIOS ===\n";
    
    // Test 1: Self pickup + toko payment (simplest scenario)
    echo "\nScenario 1: Self Pickup + Bayar di Toko\n";
    echo "- Delivery: self (no shipping fields required)\n";
    echo "- Payment: toko (no payment gateway)\n";
    echo "- Required fields: name, address1, postcode, phone, email\n";
    echo "- This should be the simplest successful checkout\n";
    
    // Test 2: courier + manual payment
    echo "\nScenario 2: Courier + Manual Transfer\n";
    echo "- Delivery: courier (requires province, city, district, shipping service)\n";
    echo "- Payment: manual (bank transfer)\n";
    echo "- Additional complexity: shipping cost calculation\n";
    
    echo "\n=== RECOMMENDATIONS ===\n";
    echo "1. Test checkout with self pickup + bayar di toko first\n";
    echo "2. Check browser console for JavaScript errors\n";
    echo "3. Monitor network tab for failed requests\n";
    echo "4. Verify all required fields are filled\n";
    echo "5. Check if form submission is being prevented by validation\n";
    
    // Also test the weight calculation
    $totalWeight = 0;
    foreach ($cartItems as $item) {
        $itemWeight = 0;
        if (isset($item->options['type']) && $item->options['type'] === 'configurable') {
            $itemWeight = $item->weight ?? 0;
        } else {
            if ($item->model) {
                $itemWeight = $item->model->weight ?? 0;
            } else {
                $itemWeight = $item->weight ?? 0;
                if ($itemWeight <= 0 && isset($item->options['product_id'])) {
                    $product = Product::find($item->options['product_id']);
                    $itemWeight = $product ? ($product->weight ?? 100) : 100;
                }
            }
        }
        
        if ($itemWeight <= 0) {
            $itemWeight = 100; // Default 100 grams
        }
        
        $totalWeight += ($item->qty * $itemWeight);
    }
    
    echo "\n📏 WEIGHT CALCULATION:\n";
    echo "   Total weight: {$totalWeight} grams\n";
    echo "   " . ($totalWeight > 0 ? "✅ Weight is valid" : "❌ Weight is invalid") . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

?>
