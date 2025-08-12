<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use GuzzleHttp\Client;

$client = new Client();

$apiKey = "Ho0D8T1Ebf59683c23db234aV2uzrSn6";
$baseUrl = "https://rajaongkir.komerce.id/api/v1";

echo "=== Mencari ID Jombang, Jawa Timur ===\n\n";

try {
    echo "Mendapatkan semua kota/kabupaten...\n";
    
    $response = $client->get($baseUrl . '/city', [
        'headers' => [
            'key' => $apiKey
        ],
        'verify' => false
    ]);

    $allCities = json_decode($response->getBody()->getContents(), true);
    
    if (isset($allCities['data'])) {
        echo "Total kota ditemukan: " . count($allCities['data']) . "\n\n";
        
        $jombangCities = [];
        $jawaTimurProvince = null;
        
        foreach ($allCities['data'] as $city) {
            if (stripos($city['city_name'], 'jombang') !== false) {
                $jombangCities[] = $city;
            }
            
            if ($city['province'] == 'Jawa Timur' && !$jawaTimurProvince) {
                $jawaTimurProvince = $city['province_id'];
            }
        }
        
        echo "Provinsi Jawa Timur ID: " . $jawaTimurProvince . "\n\n";
        
        echo "Kota/Kabupaten yang mengandung 'Jombang':\n";
        foreach ($jombangCities as $city) {
            echo "- ID: {$city['city_id']}, Nama: {$city['city_name']}, Provinsi: {$city['province']}, Tipe: {$city['type']}\n";
        }
        
        if (!empty($jombangCities)) {
            $jombangId = $jombangCities[0]['city_id'];
            echo "\nMenggunakan Jombang ID: {$jombangId}\n";
            echo "Nama lengkap: {$jombangCities[0]['type']} {$jombangCities[0]['city_name']}\n";
        }
        
    } else {
        echo "Data kota tidak ditemukan dalam response\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>
