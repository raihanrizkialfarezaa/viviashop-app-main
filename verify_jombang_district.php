<?php
require_once 'vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client([
    'verify' => false,
    'timeout' => 10
]);

$baseUrl = 'https://rajaongkir.komerce.id/api/v1';
$apiKey = 'mX8UOUC63dc7a4d50f35001eVcaMa8te';

echo "=== Verifying Jombang District ID ===\n";

// Test a broader range to find pattern differences
$testIds = [270, 271, 272, 273, 274, 275, 276, 277, 278, 279, 280];
$destinations = [
    '153' => 'Jakarta Selatan',
    '39' => 'Bandung', 
    '398' => 'Surabaya',
    '273' => 'Same District (Local)'
];

foreach ($testIds as $districtId) {
    echo "\n--- Testing District ID: $districtId ---\n";
    
    $costsFound = false;
    foreach ($destinations as $destId => $destName) {
        try {
            $response = $client->get("$baseUrl/cost", [
                'headers' => [
                    'key' => $apiKey
                ],
                'query' => [
                    'origin' => $districtId,
                    'destination' => $destId,
                    'weight' => 1000,
                    'courier' => 'jne'
                ]
            ]);
            
            $data = json_decode($response->getBody(), true);
            
            if ($data['rajaongkir']['status']['code'] == 200 && !empty($data['rajaongkir']['results'])) {
                $costsFound = true;
                $cost = $data['rajaongkir']['results'][0]['costs'][0]['cost'][0]['value'] ?? 0;
                echo "  → To $destName: Rp " . number_format($cost) . "\n";
            }
        } catch (Exception $e) {
            // Skip errors
        }
    }
    
    if (!$costsFound) {
        echo "  ✗ District $districtId is INVALID\n";
    } else {
        echo "  ✓ District $districtId is VALID\n";
    }
}

echo "\n=== Summary ===\n";
echo "Based on the cost patterns above, you can determine:\n";
echo "1. Which district ID gives the best local rates\n";
echo "2. Which district ID shows 'same district' pricing (lowest cost)\n";
echo "3. The optimal origin for your Jombang store location\n";
