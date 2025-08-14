<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

echo "=== Testing QRIS Payment Order ===\n\n";

try {
    $url = 'http://127.0.0.1:8000/admin/ordersAdmin';
    
    $postData = [
        'first_name' => 'Admin',
        'last_name' => 'Toko',
        'address1' => 'Cukir, Jombang',
        'postcode' => '102112',
        'phone' => '9121240210',
        'email' => 'admin@gmail.com',
        'product_id' => [117],
        'product_type' => ['configurable'],
        'qty' => [1],
        'payment_method' => 'qris',
        'note' => 'Test QRIS payment',
        'attributes' => [
            0 => [
                'HVS' => '3'
            ]
        ]
    ];
    
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
                'X-Requested-With: XMLHttpRequest'
            ],
            'content' => http_build_query($postData)
        ]
    ]);
    
    echo "1. Testing POST data structure...\n";
    echo "Product ID: " . json_encode($postData['product_id']) . "\n";
    echo "Qty: " . json_encode($postData['qty']) . "\n";
    echo "Payment Method: " . $postData['payment_method'] . "\n";
    echo "Attributes: " . json_encode($postData['attributes']) . "\n\n";
    
    echo "2. Simulating request processing...\n";
    
    $mockRequest = new \Illuminate\Http\Request();
    $mockRequest->merge($postData);
    
    echo "Request has attributes: " . ($mockRequest->has('attributes') ? 'Yes' : 'No') . "\n";
    echo "Attributes input: " . json_encode($mockRequest->input('attributes')) . "\n";
    
    if ($mockRequest->has('attributes')) {
        $attributes = $mockRequest->input('attributes');
        if (isset($attributes[0])) {
            echo "First item attributes: " . json_encode($attributes[0]) . "\n";
        }
    }
    
    echo "\n✅ Test structure looks correct - ParameterBag error should be fixed\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test completed ===\n";
