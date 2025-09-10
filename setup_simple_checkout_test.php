<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Product;
use App\Models\User;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Auth;

echo "=== SETTING UP SIMPLE PRODUCT CHECKOUT TEST ===\n\n";

try {
    // Clear cart
    Cart::destroy();
    echo "1. Cart cleared\n";
    
    // Find a simple product
    $simpleProduct = Product::where('type', 'simple')
        ->whereHas('productInventory', function($query) {
            $query->where('qty', '>', 0);
        })
        ->with(['productInventory', 'productImages'])
        ->first();
    
    if (!$simpleProduct) {
        echo "❌ No simple product with inventory found\n";
        exit;
    }
    
    echo "2. Found simple product: {$simpleProduct->name} (ID: {$simpleProduct->id})\n";
    
    // Login user
    $user = User::first();
    if (!$user) {
        echo "❌ No user found\n";
        exit;
    }
    
    Auth::login($user);
    echo "3. Logged in as: {$user->name}\n";
    
    // Add to cart with proper model
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
    
    echo "4. ✅ Simple product added to cart\n";
    echo "   Cart count: " . Cart::content()->count() . "\n";
    echo "   Cart subtotal: " . Cart::subtotal() . "\n";
    
    $cartItem = Cart::content()->first();
    echo "   Model exists: " . ($cartItem->model ? 'Yes' : 'No') . "\n";
    
    echo "\n5. 🎯 READY FOR CHECKOUT TEST!\n";
    echo "\nNow you can:\n";
    echo "1. Open browser and go to: http://127.0.0.1:8000/orders/checkout\n";
    echo "2. Fill the form with simple product checkout\n";
    echo "3. Try the simplest scenario: Self Pickup + Bayar di Toko\n";
    echo "4. Check browser console for JavaScript errors\n";
    echo "5. Check network tab for failed requests\n";
    echo "6. Monitor Laravel logs for backend errors\n";
    
    echo "\nORĞ\n";
    echo "Test direct checkout with: http://127.0.0.1:8000/orders/test-simple-checkout\n";
    
    echo "\n=== CART READY - PRESS ANY KEY TO EXIT ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
