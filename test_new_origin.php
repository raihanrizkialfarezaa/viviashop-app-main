<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use GuzzleHttp\Client;

$client = new Client();

$apiKey = 'Ho0D8T1Ebf59683c23db234aV2uzrSn6';
$baseUrl = 'https://rajaongkir.komerce.id/api/v1';

echo "=== Verifying New Origin Configuration ===\n\n";

echo "Testing with new origin (23) to Mojokerto:\n";

$testWeights = [1, 2, 5];

foreach ($testWeights as $weight) {
    echo "Weight: {$weight} kg\n";
    
    try {
        $response = $client->post($baseUrl . '/calculate/district/domestic-cost', [
            'headers' => [
                'key' => $apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'origin' => '23',
                'destination' => '293',
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
    } catch (Exception $e) {
        echo "  Exception: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "Testing other couriers from origin 23:\n";

$couriers = ['tiki', 'pos', 'jnt'];

foreach ($couriers as $courier) {
    echo "Courier: " . strtoupper($courier) . "\n";
    
    try {
        $response = $client->post($baseUrl . '/calculate/district/domestic-cost', [
            'headers' => [
                'key' => $apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'origin' => '23',
                'destination' => '293',
                'weight' => 1,
                'courier' => $courier
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

?>
