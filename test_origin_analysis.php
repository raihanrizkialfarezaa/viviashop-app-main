<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use GuzzleHttp\Client;

$client = new Client();

$apiKey = 'Ho0D8T1Ebf59683c23db234aV2uzrSn6';
$baseUrl = 'https://rajaongkir.komerce.id/api/v1';

echo "=== Investigating Origin City 151 ===\n\n";

try {
    $response = $client->get($baseUrl . '/province', [
        'headers' => [
            'key' => $apiKey
        ],
        'verify' => false
    ]);

    $provinces = json_decode($response->getBody()->getContents(), true);
    
    echo "Available provinces:\n";
    if (isset($provinces['data'])) {
        foreach ($provinces['data'] as $province) {
            echo "- {$province['province_id']}: {$province['province']}\n";
        }
    }
    
    echo "\nTesting cities in different provinces...\n";
    
    $response = $client->post($baseUrl . '/city', [
        'headers' => [
            'key' => $apiKey,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ],
        'form_params' => [
            'province' => '5'
        ],
        'verify' => false
    ]);

    $cities = json_decode($response->getBody()->getContents(), true);
    
    if (isset($cities['data'])) {
        foreach ($cities['data'] as $city) {
            if ($city['city_id'] == '151') {
                echo "\nOrigin City 151: {$city['city_name']}, {$city['province']}\n";
                break;
            }
        }
    }
    
    echo "\nTesting nearby cities with better rates...\n";
    
    $nearbyCities = ['152', '23', '153', '154'];
    
    foreach ($nearbyCities as $cityId) {
        echo "Testing from origin {$cityId} to Mojokerto (293):\n";
        
        $response = $client->post($baseUrl . '/calculate/district/domestic-cost', [
            'headers' => [
                'key' => $apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'origin' => $cityId,
                'destination' => '293',
                'weight' => 1,
                'courier' => 'jne'
            ],
            'verify' => false
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        
        if (isset($data['meta']['code']) && $data['meta']['code'] == 200) {
            if (!empty($data['data'])) {
                foreach ($data['data'] as $service) {
                    echo "  - {$service['service']}: Rp " . number_format($service['cost']) . "\n";
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
