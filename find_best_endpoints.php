<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use GuzzleHttp\Client;

$client = new Client();
$apiKey = "mX8UOUC63dc7a4d50f35001eVcaMa8te";
$baseUrl = "https://rajaongkir.komerce.id/api/v1";

echo "=== Testing Available Endpoints ===\n";

$endpoints = [
    '/province',
    '/get/province', 
    '/provinces',
    '/city',
    '/get/city',
    '/cities',
    '/district',
    '/get/district',
    '/districts'
];

foreach ($endpoints as $endpoint) {
    echo "Testing: {$endpoint}\n";
    
    try {
        $response = $client->get($baseUrl . $endpoint, [
            'headers' => [
                'key' => $apiKey
            ],
            'verify' => false,
            'timeout' => 10
        ]);
        
        $statusCode = $response->getStatusCode();
        echo "  Status: {$statusCode}\n";
        
        if ($statusCode == 200) {
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (isset($data['data']) && is_array($data['data'])) {
                echo "  Found data array with " . count($data['data']) . " items\n";
                
                if (!empty($data['data'])) {
                    $sample = $data['data'][0];
                    echo "  Sample keys: " . implode(', ', array_keys($sample)) . "\n";
                    
                    if (isset($sample['province']) || isset($sample['province_name'])) {
                        echo "  → This looks like PROVINCE data\n";
                        
                        foreach ($data['data'] as $item) {
                            $provinceName = $item['province'] ?? $item['province_name'] ?? '';
                            if (stripos($provinceName, 'jawa timur') !== false) {
                                $provinceId = $item['province_id'] ?? $item['id'] ?? '';
                                echo "  → Found Jawa Timur: ID = {$provinceId}, Name = {$provinceName}\n";
                                break;
                            }
                        }
                    }
                    
                    if (isset($sample['city_name']) || isset($sample['district_name'])) {
                        echo "  → This looks like CITY/DISTRICT data\n";
                        
                        foreach ($data['data'] as $item) {
                            $cityName = $item['city_name'] ?? $item['district_name'] ?? '';
                            if (stripos($cityName, 'jombang') !== false) {
                                $cityId = $item['city_id'] ?? $item['district_id'] ?? $item['id'] ?? '';
                                echo "  → Found Jombang: ID = {$cityId}, Name = {$cityName}\n";
                                break;
                            }
                        }
                    }
                }
            } else {
                echo "  No data array found\n";
            }
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        if (strpos($message, '404') !== false) {
            echo "  404 Not Found\n";
        } elseif (strpos($message, '429') !== false) {
            echo "  429 Rate Limited\n";
        } else {
            echo "  Error: {$message}\n";
        }
    }
    
    echo "\n";
}

echo "=== Testing POST endpoints with empty data ===\n";

$postEndpoints = [
    '/get/city',
    '/get/district'
];

foreach ($postEndpoints as $endpoint) {
    echo "Testing POST: {$endpoint}\n";
    
    try {
        $response = $client->post($baseUrl . $endpoint, [
            'headers' => [
                'key' => $apiKey,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => [],
            'verify' => false,
            'timeout' => 10
        ]);
        
        $statusCode = $response->getStatusCode();
        echo "  Status: {$statusCode}\n";
        
        if ($statusCode == 200) {
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (isset($data['data']) && is_array($data['data'])) {
                echo "  Found data array with " . count($data['data']) . " items\n";
                
                if (!empty($data['data'])) {
                    $sample = $data['data'][0];
                    echo "  Sample keys: " . implode(', ', array_keys($sample)) . "\n";
                    
                    if (isset($sample['city_name'])) {
                        echo "  → This is CITY data\n";
                        
                        foreach ($data['data'] as $item) {
                            if (stripos($item['city_name'], 'jombang') !== false) {
                                echo "  → Found Jombang: ID = {$item['city_id']}, Name = {$item['city_name']}, Province = {$item['province']}\n";
                                break;
                            }
                        }
                    }
                    
                    if (isset($sample['district_name'])) {
                        echo "  → This is DISTRICT data\n";
                        
                        foreach ($data['data'] as $item) {
                            if (stripos($item['district_name'], 'jombang') !== false) {
                                echo "  → Found Jombang District: ID = {$item['district_id']}, Name = {$item['district_name']}\n";
                                break;
                            }
                        }
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        if (strpos($message, '404') !== false) {
            echo "  404 Not Found\n";
        } elseif (strpos($message, '429') !== false) {
            echo "  429 Rate Limited\n";
        } else {
            echo "  Error: {$message}\n";
        }
    }
    
    echo "\n";
}

?>
