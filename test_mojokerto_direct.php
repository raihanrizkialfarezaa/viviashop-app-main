<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use GuzzleHttp\Client;

$client = new Client();

$apiKey = 'Ho0D8T1Ebf59683c23db234aV2uzrSn6';
$baseUrl = 'https://rajaongkir.komerce.id/api/v1';

echo "=== Direct API Test to Mojokerto ===\n\n";

$testCases = [
    ['weight' => 1, 'desc' => '1 kg'],
    ['weight' => 2, 'desc' => '2 kg'],
    ['weight' => 5, 'desc' => '5 kg'],
    ['weight' => 10, 'desc' => '10 kg']
];

$mojokertoIds = [293, 294];

foreach ($mojokertoIds as $cityId) {
    echo "Testing to city ID: {$cityId}\n";
    
    foreach ($testCases as $test) {
        echo "Weight: {$test['desc']}\n";
        
        try {
            $response = $client->post($baseUrl . '/calculate/district/domestic-cost', [
                'headers' => [
                    'key' => $apiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    'origin' => '151',
                    'destination' => $cityId,
                    'weight' => $test['weight'],
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
                } else {
                    echo "  No services available\n";
                }
            } else {
                echo "  Error: " . ($data['meta']['message'] ?? 'Unknown error') . "\n";
            }
        } catch (Exception $e) {
            echo "  Exception: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
    echo "---\n\n";
}

echo "=== Testing different origin cities ===\n\n";

$origins = [151, 152, 23];

foreach ($origins as $origin) {
    echo "From origin {$origin} to Mojokerto (293), 1kg JNE:\n";
    
    try {
        $response = $client->post($baseUrl . '/calculate/district/domestic-cost', [
            'headers' => [
                'key' => $apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'origin' => $origin,
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
    } catch (Exception $e) {
        echo "  Exception: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

?>
