<?php
// Test Laravel endpoints directly

function testLaravelEndpoint($url, $method = 'GET', $data = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "URL: " . $url . "\n";
    echo "Method: " . $method . "\n";
    echo "HTTP Code: " . $httpCode . "\n";
    echo "Response: " . $response . "\n";
    echo str_repeat("=", 50) . "\n";
}

echo "Testing Laravel endpoints...\n\n";

$baseUrl = 'http://localhost:8000';

// Test provinces
testLaravelEndpoint($baseUrl . '/orders/cities?province_id=18');

// Test districts
testLaravelEndpoint($baseUrl . '/orders/districts?city_id=389');

// Test shipping cost (need CSRF token for POST, but let's try anyway)
testLaravelEndpoint($baseUrl . '/orders/shipping-cost', 'POST', [
    'district_id' => 3852,
    '_token' => 'test-token'  // This will fail without valid CSRF token
]);
