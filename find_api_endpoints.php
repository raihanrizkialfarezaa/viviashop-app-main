<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use GuzzleHttp\Client;

$client = new Client();

$apiKey = "Ho0D8T1Ebf59683c23db234aV2uzrSn6";

echo "=== Menguji endpoint API yang tersedia ===\n\n";

$endpoints = [
    '/district',
    '/subdistrict', 
    '/districts',
    '/city',
    '/province'
];

foreach ($endpoints as $endpoint) {
    echo "Testing endpoint: {$endpoint}\n";
    
    try {
        $response = $client->get("https://rajaongkir.komerce.id/api/v1{$endpoint}", [
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
            echo "  Response structure: " . (isset($data['data']) ? 'Has data array' : 'Different structure') . "\n";
            
            if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
                $sample = $data['data'][0];
                echo "  Sample fields: " . implode(', ', array_keys($sample)) . "\n";
                
                if (isset($sample['city_name']) || isset($sample['district_name'])) {
                    echo "  Total items: " . count($data['data']) . "\n";
                }
            }
        }
        
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== Mencoba mencari Jombang di district ===\n\n";

try {
    $response = $client->get("https://rajaongkir.komerce.id/api/v1/district", [
        'headers' => [
            'key' => $apiKey
        ],
        'verify' => false
    ]);

    $data = json_decode($response->getBody()->getContents(), true);
    
    if (isset($data['data'])) {
        $jombangFound = [];
        
        foreach ($data['data'] as $district) {
            if (stripos($district['district_name'], 'jombang') !== false) {
                $jombangFound[] = $district;
            }
        }
        
        echo "District yang mengandung 'Jombang':\n";
        foreach ($jombangFound as $district) {
            echo "- ID: {$district['district_id']}, Nama: {$district['district_name']}, Kota: {$district['city_name']}, Provinsi: {$district['province']}\n";
        }
        
        if (!empty($jombangFound)) {
            $selectedJombang = $jombangFound[0];
            echo "\nMenggunakan District Jombang:\n";
            echo "District ID: {$selectedJombang['district_id']}\n";
            echo "District Name: {$selectedJombang['district_name']}\n";
            echo "City: {$selectedJombang['city_name']}\n";
            echo "Province: {$selectedJombang['province']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>
