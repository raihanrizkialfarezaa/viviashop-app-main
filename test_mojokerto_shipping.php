<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use GuzzleHttp\Client;

$client = new Client();

$apiKey = 'Ho0D8T1Ebf59683c23db234aV2uzrSn6';
$baseUrl = 'https://rajaongkir.komerce.id/api/v1';

echo "=== Testing Mojokerto Shipping Cost Analysis ===\n\n";

echo "Finding Mojokerto city ID...\n";

try {
    $response = $client->get($baseUrl . '/city', [
        'headers' => [
            'key' => $apiKey
        ],
        'verify' => false
    ]);

    $data = json_decode($response->getBody()->getContents(), true);
    
    $mojokertoCities = [];
    if (isset($data['data'])) {
        foreach ($data['data'] as $city) {
            if (stripos($city['city_name'], 'mojokerto') !== false) {
                $mojokertoCities[] = $city;
            }
        }
    }
    
    echo "Found Mojokerto cities:\n";
    foreach ($mojokertoCities as $city) {
        echo "- ID: {$city['city_id']}, Name: {$city['city_name']}, Province: {$city['province']}\n";
    }
    
    if (empty($mojokertoCities)) {
        echo "No Mojokerto cities found!\n";
        exit;
    }
    
    $mojokertoId = $mojokertoCities[0]['city_id'];
    echo "\nUsing Mojokerto ID: {$mojokertoId}\n\n";
    
    $testWeights = [1, 1000, 2000, 5000];
    
    foreach ($testWeights as $weight) {
        echo "Testing weight: {$weight} (treating as " . ($weight >= 1000 ? ($weight/1000) . "kg" : $weight . "g") . ")\n";
        
        $response = $client->post($baseUrl . '/calculate/district/domestic-cost', [
            'headers' => [
                'key' => $apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'origin' => '151',
                'destination' => $mojokertoId,
                'weight' => $weight,
                'courier' => 'jne'
            ],
            'verify' => false
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        
        if (isset($data['meta']['code']) && $data['meta']['code'] == 200) {
            if (!empty($data['data'])) {
                foreach ($data['data'] as $service) {
                    echo "  - {$service['service']}: Rp " . number_format($service['cost']) . " ({$service['etd']})\n";
                }
            }
        } else {
            echo "  Error: " . ($data['meta']['message'] ?? 'Unknown error') . "\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>
