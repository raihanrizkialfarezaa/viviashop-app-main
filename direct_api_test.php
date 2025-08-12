<?php
// Direct API test

$apiKey = 'mX8UOUC63dc7a4d50f35001eVcaMa8te';
$baseUrl = 'https://rajaongkir.komerce.id/api/v1/';

function testDirectAPI($url, $apiKey, $method = 'GET', $postData = null) {
    $ch = curl_init();
    
    $headers = [
        'key: ' . $apiKey,
        'Accept: application/json'
    ];
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method === 'POST' && $postData) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    echo "URL: " . $url . "\n";
    echo "HTTP Code: " . $httpCode . "\n";
    echo "cURL Error: " . ($error ?: 'None') . "\n";
    echo "Raw Response: " . $response . "\n";
    echo "Decoded Response:\n";
    print_r(json_decode($response, true));
    echo "\n" . str_repeat("=", 50) . "\n";
}

echo "Testing Direct API Calls\n\n";

// Test provinces
testDirectAPI($baseUrl . 'wilayah/provinsi', $apiKey);

// Test cities for Jawa Timur (ID: 18)
testDirectAPI($baseUrl . 'wilayah/kabupaten?id_provinsi=18', $apiKey);

// Test districts for Jombang (ID: 389)
testDirectAPI($baseUrl . 'wilayah/kecamatan?id_kabupaten=389', $apiKey);

// Test shipping calculation
testDirectAPI($baseUrl . 'calculate/district/domestic-cost', $apiKey, 'POST', [
    'origin' => 3852,
    'destination' => 3852,
    'weight' => 1000,
    'courier' => 'jne'
]);
