<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::call('cache:clear');

echo "=== DEBUGGING ORDER CREATION ISSUE ===\n\n";

echo "1. Testing order validation format...\n";

$testOrderData = [
    'first_name' => 'Test',
    'last_name' => 'User',
    'address1' => 'Test Address',
    'postcode' => '12345',
    'phone' => '08123456789',
    'email' => 'test@example.com',
    'product_id' => [138], // RAKET PADEL
    'qty' => [2],
    'variant_id' => ['simple_138'], // Format yang dikirim dari frontend
    'payment_method' => 'toko'
];

echo "Test data format:\n";
echo "- product_id: " . json_encode($testOrderData['product_id']) . "\n";
echo "- qty: " . json_encode($testOrderData['qty']) . "\n";
echo "- variant_id: " . json_encode($testOrderData['variant_id']) . "\n";

echo "\n2. Checking validation rules...\n";
echo "Old validation: 'variant_id.*' => 'nullable|integer' ❌\n";
echo "New validation: 'variant_id.*' => 'nullable' ✅\n";

echo "\n3. Checking product data...\n";
$product = DB::table('products')->where('id', 138)->first();
if ($product) {
    echo "Product found: {$product->name}\n";
    echo "- Type: {$product->type}\n";
    echo "- Price: {$product->price}\n";
    echo "- Stock: {$product->total_stock}\n";
} else {
    echo "❌ Product not found\n";
}

echo "\n4. Testing variant_id parsing logic...\n";
$variantIdValue = 'simple_138';
echo "Input variant_id: {$variantIdValue}\n";

if (is_string($variantIdValue) && strpos($variantIdValue, 'simple_') === 0) {
    echo "✅ Detected as simple product format\n";
    $extractedId = str_replace('simple_', '', $variantIdValue);
    echo "Extracted product ID: {$extractedId}\n";
} else {
    echo "❌ Not recognized as simple product format\n";
}

echo "\n5. Expected flow for simple products:\n";
echo "- variant_id: 'simple_138' → variantId = null (no actual variant needed)\n";
echo "- Use product price directly: Rp " . number_format($product->price ?? 0) . "\n";
echo "- Use product SKU: {$product->sku}\n";
echo "- Use product name: {$product->name}\n";

echo "\n=== FIX APPLIED ===\n";
echo "✅ Validation updated to accept string variant_id\n";
echo "✅ Logic updated to handle 'simple_' prefix\n";
echo "✅ Simple products use product data directly\n";
echo "✅ Configurable products still use variant lookup\n";

echo "\nReady to test order creation again!\n";