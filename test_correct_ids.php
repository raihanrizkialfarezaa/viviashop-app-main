<?php

echo "=== TESTING CORRECT JAWA TIMUR ID ===\n\n";

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

// Test cities for Jawa Timur (ID: 18)
echo "Testing cities for Jawa Timur (ID: 18)...\n";
$citiesUrl = $baseUrl . 'destination/city/18';
$citiesResponse = makeRawRequest($citiesUrl, $apiKey);

if (isset($citiesResponse['data'])) {
    echo "Cities in Jawa Timur:\n";
    $mojokerto = null;
    
    foreach ($citiesResponse['data'] as $city) {
        echo "- ID: {$city['id']}, Name: {$city['name']}\n";
        
        if (strpos(strtoupper($city['name']), 'MOJOKERTO') !== false) {
            $mojokerto = $city;
        }
    }
    
    if ($mojokerto) {
        echo "\n✅ Found Mojokerto: ID = {$mojokerto['id']}, Name = {$mojokerto['name']}\n\n";
        
        // Test districts for Mojokerto
        echo "Testing districts for Mojokerto (ID: {$mojokerto['id']})...\n";
        $districtsUrl = $baseUrl . 'destination/district/' . $mojokerto['id'];
        $districtsResponse = makeRawRequest($districtsUrl, $apiKey);
        
        if (isset($districtsResponse['data'])) {
            echo "Districts in Mojokerto:\n";
            $count = 0;
            foreach ($districtsResponse['data'] as $district) {
                echo "- ID: {$district['id']}, Name: {$district['name']}\n";
                $count++;
                if ($count >= 10) {
                    echo "... and " . (count($districtsResponse['data']) - 10) . " more districts\n";
                    break;
                }
            }
            echo "\n✅ NEW API KEY WORKING PERFECTLY!\n";
            echo "✅ Correct IDs: Jawa Timur = 18, Mojokerto = {$mojokerto['id']}\n";
        }
    } else {
        echo "\n❌ Mojokerto not found in Jawa Timur cities\n";
    }
} else {
    echo "❌ Failed to get cities\n";
    print_r($citiesResponse);
}

?>
