<?php
// Test district endpoints

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

echo "Testing district endpoints for Jombang (ID: 389)...\n\n";

$districtEndpoints = [
    'destination/district/389',
    'wilayah/kecamatan?id_kabupaten=389',
    'location/district/389',
    'districts/389',
    'district/389',
    'wilayah/district?city_id=389',
    'address/district?city_id=389'
];

foreach ($districtEndpoints as $endpoint) {
    testEndpoint($baseUrl . $endpoint, $apiKey);
}
