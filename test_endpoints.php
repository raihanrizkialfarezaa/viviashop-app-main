<?php
// Test different endpoint variations

$apiKey = 'mX8UOUC63dc7a4d50f35001eVcaMa8te';
$baseUrl = 'https://rajaongkir.komerce.id/api/v1/';

function testEndpoint($url, $apiKey) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'key: ' . $apiKey,
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "URL: " . $url . " => HTTP " . $httpCode . "\n";
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['data']) && is_array($data['data'])) {
            echo "SUCCESS: Found " . count($data['data']) . " items\n";
            if (count($data['data']) > 0) {
                echo "Sample data structure:\n";
                print_r($data['data'][0]);
            }
        }
        echo "\n";
    }
}

echo "Testing various endpoint patterns...\n\n";

// Try different province endpoints
$endpoints = [
    'wilayah/provinsi',
    'destination/province',
    'location/province',
    'provinces',
    'province',
    'wilayah/province',
    'address/province'
];

foreach ($endpoints as $endpoint) {
    testEndpoint($baseUrl . $endpoint, $apiKey);
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Testing city endpoints for province 18...\n\n";

$cityEndpoints = [
    'wilayah/kabupaten?id_provinsi=18',
    'destination/city/18',
    'location/city/18', 
    'cities/18',
    'city/18',
    'wilayah/city?province_id=18',
    'address/city?province_id=18'
];

foreach ($cityEndpoints as $endpoint) {
    testEndpoint($baseUrl . $endpoint, $apiKey);
}
