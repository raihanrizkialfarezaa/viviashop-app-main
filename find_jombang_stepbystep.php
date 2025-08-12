<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use GuzzleHttp\Client;

$client = new Client();
$apiKey = "mX8UOUC63dc7a4d50f35001eVcaMa8te";
$baseUrl = "https://rajaongkir.komerce.id/api/v1";

echo "=== Step 1: Get All Provinces ===\n";

try {
    $response = $client->get($baseUrl . '/get/province', [
        'headers' => [
            'key' => $apiKey
        ],
        'verify' => false
    ]);

    $provinces = json_decode($response->getBody()->getContents(), true);
    
    if (isset($provinces['data'])) {
        echo "Total provinces: " . count($provinces['data']) . "\n";
        
        $jawaTimurId = null;
        foreach ($provinces['data'] as $province) {
            if (stripos($province['province'], 'jawa timur') !== false) {
                $jawaTimurId = $province['province_id'];
                echo "Found Jawa Timur - ID: {$jawaTimurId}, Name: {$province['province']}\n";
                break;
            }
        }
        
        if ($jawaTimurId) {
            echo "\n=== Step 2: Get Cities in Jawa Timur ===\n";
            
            $response = $client->post($baseUrl . '/get/city', [
                'headers' => [
                    'key' => $apiKey,
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    'province_id' => $jawaTimurId
                ],
                'verify' => false
            ]);

            $cities = json_decode($response->getBody()->getContents(), true);
            
            if (isset($cities['data'])) {
                echo "Total cities in Jawa Timur: " . count($cities['data']) . "\n";
                
                $jombangCityId = null;
                foreach ($cities['data'] as $city) {
                    if (stripos($city['city_name'], 'jombang') !== false) {
                        $jombangCityId = $city['city_id'];
                        echo "Found Jombang - City ID: {$jombangCityId}, Name: {$city['city_name']}, Type: {$city['type']}\n";
                        break;
                    }
                }
                
                if ($jombangCityId) {
                    echo "\n=== Step 3: Get Districts in Jombang ===\n";
                    
                    $response = $client->post($baseUrl . '/get/district', [
                        'headers' => [
                            'key' => $apiKey,
                            'Content-Type' => 'application/x-www-form-urlencoded'
                        ],
                        'form_params' => [
                            'city_id' => $jombangCityId
                        ],
                        'verify' => false
                    ]);

                    $districts = json_decode($response->getBody()->getContents(), true);
                    
                    if (isset($districts['data'])) {
                        echo "Total districts in Jombang: " . count($districts['data']) . "\n";
                        
                        foreach ($districts['data'] as $district) {
                            echo "- District ID: {$district['district_id']}, Name: {$district['district_name']}\n";
                        }
                        
                        if (!empty($districts['data'])) {
                            $firstDistrictId = $districts['data'][0]['district_id'];
                            echo "\nUsing first district as origin: {$firstDistrictId}\n";
                            echo "District name: {$districts['data'][0]['district_name']}\n";
                        }
                    }
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>
