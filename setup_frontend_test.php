<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Product;
use App\Models\User;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Auth;

echo "=== SETTING UP SIMPLE PRODUCT FOR FRONTEND TEST ===\n\n";

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
        echo "❌ No simple product found\n";
        exit;
    }
    
    echo "2. Found simple product: {$simpleProduct->name} (ID: {$simpleProduct->id})\n";
    
    // Login user
    $user = User::first();
    Auth::login($user);
    echo "3. Logged in as: {$user->name}\n";
    
    // Use CartController to properly add product
    $cartController = new \App\Http\Controllers\Frontend\CartController(
        app(\App\Services\ProductVariantService::class)
    );
    
    $request = new \Illuminate\Http\Request();
    $request->merge([
        'product_id' => $simpleProduct->id,
        'qty' => 1
    ]);
    $request->headers->set('Accept', 'application/json');
    
    $cartResponse = $cartController->store($request);
    $cartData = json_decode($cartResponse->getContent(), true);
    
    if ($cartData['status'] !== 'success') {
        echo "❌ Failed to add to cart: " . $cartData['message'] . "\n";
        exit;
    }
    
    echo "4. ✅ Product added to cart successfully\n";
    echo "   Cart count: " . Cart::content()->count() . "\n";
    
    echo "\n🎯 READY FOR FRONTEND CHECKOUT TEST!\n";
    echo "\nNow open browser and test:\n";
    echo "URL: http://127.0.0.1:8000/orders/checkout\n";
    echo "\nScenario to test:\n";
    echo "1. Fill form with user data\n";
    echo "2. Select 'Self Pickup' (default)\n";
    echo "3. Select 'Bayar Di Toko' payment\n";
    echo "4. Click 'PLACE ORDER'\n";
    echo "5. Should redirect to success page, NOT refresh!\n";
    
    echo "\n📋 Frontend fixes applied:\n";
    echo "✅ Enhanced form validation with better error messages\n";
    echo "✅ Added comprehensive JavaScript error logging\n";
    echo "✅ Fixed handleFormSubmit function\n";
    echo "✅ Added debug information\n";
    echo "✅ Improved total amount calculation\n";
    
    echo "\n⚠️  If still having issues, check:\n";
    echo "1. Browser console for JavaScript errors\n";
    echo "2. Network tab for failed requests\n";
    echo "3. Form data being submitted correctly\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
