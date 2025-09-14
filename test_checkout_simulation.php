<?php

require_once './vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use Gloudemans\Shoppingcart\Facades\Cart;

$app = require_once './bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CHECKOUT FLOW SIMULATION TEST ===\n\n";

echo "1. SETUP TEST USER AND CART:\n";

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
    echo "✓ Created test user\n";
} else {
    echo "✓ Using existing test user\n";
}

echo "User ID: {$testUser->id}\n";
echo "User Email: {$testUser->email}\n";

$products = Product::take(1)->get();
if ($products->count() > 0) {
    $product = $products->first();
    echo "✓ Found test product: {$product->name} (ID: {$product->id})\n";
} else {
    echo "❌ No products found for testing\n";
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

echo "✓ Added product to cart\n";
echo "Cart count: " . Cart::count() . "\n";
echo "Cart content: " . Cart::content()->count() . " items\n";

echo "\n2. SIMULATE CHECKOUT VALIDATION:\n";

$checkoutData = [
    'name' => 'Test User',
    'address1' => 'Test Address 1',
    'address2' => 'Test Address 2',
    'postcode' => '12345',
    'phone' => '08123456789',
    'email' => 'test@example.com',
    'delivery_method' => 'self',
    'payment_method' => 'manual',
    'unique_code' => 123,
    'total_amount' => 10123,
    'note' => 'Test order note'
];

echo "Test data prepared:\n";
foreach($checkoutData as $key => $value) {
    echo "  $key: $value\n";
}

echo "\n3. VALIDATION RULES TEST:\n";

$validationRules = [
    'name' => 'required|string|max:255',
    'address1' => 'required|string|max:255',
    'address2' => 'nullable|string|max:255',
    'postcode' => 'required|string|max:20',
    'phone' => 'required|string|max:15',
    'email' => 'required|email|max:255',
    'payment_method' => 'required|string|in:manual,automatic,cod,toko',
    'delivery_method' => 'required|string|in:self,courier',
];

$validator = \Illuminate\Support\Facades\Validator::make($checkoutData, $validationRules);

if ($validator->fails()) {
    echo "❌ Validation failed:\n";
    foreach($validator->errors()->all() as $error) {
        echo "  - $error\n";
    }
} else {
    echo "✅ Validation passed\n";
}

echo "\n4. PAYMENT SLIP LOGIC TEST:\n";

$paymentSlipShouldShow = ($checkoutData['payment_method'] === 'manual');
echo "Payment method: {$checkoutData['payment_method']}\n";
echo "Payment slip should show: " . ($paymentSlipShouldShow ? 'YES' : 'NO') . "\n";

if ($paymentSlipShouldShow) {
    echo "⚠️  ISSUE IDENTIFIED: Payment slip should NOT show in checkout page for manual payment\n";
    echo "It should only show in the NEXT page after order is created\n";
} else {
    echo "✅ Payment slip logic is correct\n";
}

echo "\n5. DELIVERY METHOD LOGIC TEST:\n";

$addressFieldsRequired = ($checkoutData['delivery_method'] === 'courier');
echo "Delivery method: {$checkoutData['delivery_method']}\n";
echo "Address fields required: " . ($addressFieldsRequired ? 'YES' : 'NO') . "\n";

if (!$addressFieldsRequired) {
    echo "✅ Self pickup - address fields not required\n";
} else {
    echo "Address fields would be required for courier delivery\n";
}

echo "\n6. ORDER CREATION SIMULATION:\n";

try {
    
    $order = new \App\Models\Order();
    $order->user_id = $testUser->id;
    $order->code = 'TEST-' . date('YmdHis');
    $order->status = 'pending';
    $order->payment_status = 'pending';
    $order->total = $checkoutData['total_amount'];
    $order->bill_to = $checkoutData['name'];
    $order->bill_address = $checkoutData['address1'];
    $order->note = $checkoutData['note'] ?? '';
    
    echo "✅ Order object created successfully\n";
    echo "Order would be saved with code: {$order->code}\n";
    
    if ($checkoutData['payment_method'] === 'manual') {
        echo "✅ Manual payment - no payment gateway required\n";
        echo "Order would redirect to: orders/received/{order_id}\n";
    } elseif ($checkoutData['payment_method'] === 'automatic') {
        echo "Payment gateway would be triggered for automatic payment\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Order creation simulation failed: " . $e->getMessage() . "\n";
}

Cart::destroy();

echo "\n=== ANALYSIS RESULTS ===\n";
echo "🔍 POTENTIAL ISSUES FOUND:\n";
echo "1. Payment slip upload should NOT be in checkout page\n";
echo "2. Payment slip should be in a separate page after order creation\n";
echo "3. For Direct Bank Transfer, flow should be:\n";
echo "   a) Checkout page (no upload field)\n";
echo "   b) Order created\n";
echo "   c) Redirect to payment page with upload field\n";
echo "   d) User uploads payment slip\n";
echo "   e) Admin confirms payment\n";

echo "\n=== TEST COMPLETE ===\n";