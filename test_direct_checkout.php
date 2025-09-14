<?php

require_once './vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use App\Http\Controllers\Frontend\OrderController;
use Illuminate\Http\Request;
use Gloudemans\Shoppingcart\Facades\Cart;

$app = require_once './bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== DIRECT CHECKOUT TEST ===\n\n";

try {
    echo "1. SETUP TEST ENVIRONMENT:\n";
    
    $testUser = User::where('email', 'test@example.com')->first();
    if (!$testUser) {
        $testUser = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'phone' => '08123456789',
            'address1' => 'Test Address 1',
            'address2' => 'Test Address 2', 
            'postcode' => '12345'
        ]);
    }
    
    auth()->login($testUser);
    echo "✓ Test user logged in\n";
    
    $product = Product::first();
    if (!$product) {
        echo "❌ No products found\n";
        exit;
    }
    
    Cart::destroy();
    Cart::add([
        'id' => $product->id,
        'name' => $product->name,
        'qty' => 1,
        'price' => $product->price ?? 10000,
        'options' => [
            'type' => 'simple',
            'product_id' => $product->id
        ]
    ]);
    
    echo "✓ Product added to cart: {$product->name}\n";
    echo "Cart count: " . Cart::count() . "\n";
    
    echo "\n2. SIMULATE CHECKOUT REQUEST:\n";
    
    $requestData = [
        '_token' => csrf_token(),
        'name' => 'Test User Checkout',
        'address1' => 'Test Address 1',
        'address2' => 'Test Address 2',
        'postcode' => '12345',
        'phone' => '08123456789',
        'email' => 'test@example.com',
        'delivery_method' => 'self',
        'payment_method' => 'manual',
        'unique_code' => '123',
        'total_amount' => '10123',
        'note' => 'Test checkout from script'
    ];
    
    echo "Request data prepared:\n";
    foreach($requestData as $key => $value) {
        if ($key !== '_token') {
            echo "  $key: $value\n";
        }
    }
    
    echo "\n3. CREATE REQUEST OBJECT:\n";
    
    $request = new Request();
    $request->merge($requestData);
    $request->setMethod('POST');
    
    echo "✓ Request object created\n";
    echo "Request method: " . $request->method() . "\n";
    echo "Has all required fields: " . ($request->has(['name', 'address1', 'phone', 'email', 'payment_method', 'delivery_method']) ? 'YES' : 'NO') . "\n";
    
    echo "\n4. EXECUTE CHECKOUT:\n";
    
    $controller = new OrderController();
    
    DB::beginTransaction();
    
    try {
        echo "Calling doCheckout method...\n";
        $response = $controller->doCheckout($request);
        
        echo "✅ doCheckout executed successfully\n";
        echo "Response type: " . get_class($response) . "\n";
        
        if (method_exists($response, 'getTargetUrl')) {
            echo "Redirect URL: " . $response->getTargetUrl() . "\n";
        }
        
        if (method_exists($response, 'status')) {
            echo "Response status: " . $response->status() . "\n";
        }
        
        DB::rollBack(); // Rollback untuk test
        echo "✓ Transaction rolled back (test mode)\n";
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "❌ Checkout failed with error:\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
        
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            echo "\nValidation errors:\n";
            foreach($e->errors() as $field => $errors) {
                echo "  $field: " . implode(', ', $errors) . "\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Test setup failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} finally {
    Cart::destroy();
    auth()->logout();
}

echo "\n=== TEST COMPLETE ===\n";