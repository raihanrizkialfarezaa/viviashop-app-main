<?php

echo "=== RAW API RESPONSE TEST ===\n\n";

$apiKey = 'Ho0D8T1Ebf59683c23db234aV2uzrSn6';
$baseUrl = 'https://rajaongkir.komerce.id/api/v1/';

function makeRawRequest($url, $apiKey) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "key: {$apiKey}\r\n"
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    return json_decode($response, true);
}

// Test provinces
echo "Testing provinces...\n";
$provincesUrl = $baseUrl . 'destination/province';
$provincesResponse = makeRawRequest($provincesUrl, $apiKey);
echo "Provinces response:\n";
print_r($provincesResponse);

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test cities for Jawa Timur (ID: 17)
echo "Testing cities for Jawa Timur (ID: 17)...\n";
$citiesUrl = $baseUrl . 'destination/city/17';
$citiesResponse = makeRawRequest($citiesUrl, $apiKey);
echo "Cities response:\n";
print_r($citiesResponse);

?>
