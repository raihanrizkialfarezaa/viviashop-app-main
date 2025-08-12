<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use GuzzleHttp\Client;

$client = new Client();
$apiKey = "mX8UOUC63dc7a4d50f35001eVcaMa8te";

echo "=== Testing Different Base URLs ===\n";

$baseUrls = [
    "https://rajaongkir.komerce.id/api/v1",
    "https://rajaongkir.komerce.id/api",
    "https://rajaongkir.komerce.id",
    "https://api.rajaongkir.com/starter",
    "https://pro.rajaongkir.com/api"
];

$testEndpoints = [
    '/province',
    '/city', 
    '/cost'
];

foreach ($baseUrls as $baseUrl) {
    echo "Testing base URL: {$baseUrl}\n";
    
    foreach ($testEndpoints as $endpoint) {
        echo "  Endpoint: {$endpoint}\n";
        
        try {
            $response = $client->get($baseUrl . $endpoint, [
                'headers' => [
                    'key' => $apiKey
                ],
                'verify' => false,
                'timeout' => 5
            ]);
            
            echo "    Status: " . $response->getStatusCode() . "\n";
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (strpos($message, '404') !== false) {
                echo "    404 Not Found\n";
            } elseif (strpos($message, '401') !== false) {
                echo "    401 Unauthorized (API key issue)\n";
            } elseif (strpos($message, '429') !== false) {
                echo "    429 Rate Limited\n";
            } elseif (strpos($message, '403') !== false) {
                echo "    403 Forbidden\n";
            } else {
                echo "    Error: " . substr($message, 0, 100) . "\n";
            }
        }
    }
    echo "\n";
}

echo "=== Testing known working cost endpoint ===\n";

$testParams = [
    'origin' => '273',
    'destination' => '152', 
    'weight' => 1,
    'courier' => 'jne'
];

try {
    $response = $client->post("https://rajaongkir.komerce.id/api/v1/calculate/district/domestic-cost", [
        'headers' => [
            'key' => $apiKey,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ],
        'form_params' => $testParams,
        'verify' => false
    ]);
    
    echo "Cost endpoint status: " . $response->getStatusCode() . "\n";
    
    if ($response->getStatusCode() == 200) {
        $data = json_decode($response->getBody()->getContents(), true);
        echo "Cost endpoint works! Response structure:\n";
        echo "  Meta: " . (isset($data['meta']) ? 'YES' : 'NO') . "\n";
        echo "  Data: " . (isset($data['data']) ? 'YES' : 'NO') . "\n";
        
        if (isset($data['meta']['message'])) {
            echo "  Message: " . $data['meta']['message'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Cost endpoint error: " . $e->getMessage() . "\n";
}

echo "\n=== Manual District Search ===\n";

echo "Since API endpoints for provinces/cities are not available,\n";
echo "we need to use known district IDs based on RajaOngkir documentation.\n\n";

echo "Common Jombang district IDs to test:\n";
$jombangIds = [273, 274, 275, 276, 277];

foreach ($jombangIds as $districtId) {
    echo "Testing district ID: {$districtId}\n";
    
    try {
        $response = $client->post("https://rajaongkir.komerce.id/api/v1/calculate/district/domestic-cost", [
            'headers' => [
                'key' => $apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [
                'origin' => $districtId,
                'destination' => '152',
                'weight' => 1,
                'courier' => 'jne'
            ],
            'verify' => false
        ]);
        
        if ($response->getStatusCode() == 200) {
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (isset($data['meta']['code']) && $data['meta']['code'] == 200) {
                echo "  ✓ District {$districtId} is VALID\n";
                
                if (!empty($data['data'])) {
                    $cost = $data['data'][0]['cost'];
                    echo "  Sample cost to Jakarta: Rp " . number_format($cost) . "\n";
                }
            } else {
                echo "  ✗ District {$districtId} returned error\n";
            }
        }
        
    } catch (Exception $e) {
        echo "  ✗ District {$districtId} failed: " . substr($e->getMessage(), 0, 50) . "\n";
    }
    
    echo "\n";
}

?>
