<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use GuzzleHttp\Client;

$client = new Client();
$apiKey = "baf168a52b7282eb517d49ced902ec2bf06e0d5362b63d14aa6aeb9d993f5228";

echo "=== Testing Binderbyte API ===\n\n";

$endpoints = [
    'https://api.binderbyte.com/wilayah/provinsi?api_key=' . $apiKey,
    'https://api.binderbyte.com/wilayah/kabupaten?api_key=' . $apiKey . '&id_provinsi=35',
    'https://api.binderbyte.com/wilayah/kecamatan?api_key=' . $apiKey . '&id_kabupaten=3517',
    'https://api.binderbyte.com/wilayah/kelurahan?api_key=' . $apiKey . '&id_kecamatan=3517150'
];

foreach ($endpoints as $endpoint) {
    echo "Testing: " . $endpoint . "\n";
    
    try {
        $response = $client->get($endpoint, [
            'verify' => false,
            'timeout' => 10
        ]);
        
        $data = json_decode($response->getBody()->getContents(), true);
        
        if (isset($data['code']) && $data['code'] == 200) {
            echo "Success - " . count($data['value']) . " items found\n";
            
            if (count($data['value']) > 0) {
                $sample = $data['value'][0];
                echo "Sample: " . json_encode($sample) . "\n";
            }
        } else {
            echo "Error: " . json_encode($data) . "\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=== Finding Jombang in Jawa Timur ===\n\n";

try {
    $response = $client->get('https://api.binderbyte.com/wilayah/provinsi?api_key=' . $apiKey, [
        'verify' => false
    ]);
    
    $provinces = json_decode($response->getBody()->getContents(), true);
    
    $jawaTimurId = null;
    foreach ($provinces['value'] as $province) {
        if (stripos($province['name'], 'jawa timur') !== false) {
            $jawaTimurId = $province['id'];
            echo "Jawa Timur ID: {$jawaTimurId}\n";
            break;
        }
    }
    
    if ($jawaTimurId) {
        $response = $client->get('https://api.binderbyte.com/wilayah/kabupaten?api_key=' . $apiKey . '&id_provinsi=' . $jawaTimurId, [
            'verify' => false
        ]);
        
        $kabupaten = json_decode($response->getBody()->getContents(), true);
        
        $jombangId = null;
        foreach ($kabupaten['value'] as $kab) {
            if (stripos($kab['name'], 'jombang') !== false) {
                $jombangId = $kab['id'];
                echo "Jombang ID: {$jombangId}\n";
                break;
            }
        }
        
        if ($jombangId) {
            $response = $client->get('https://api.binderbyte.com/wilayah/kecamatan?api_key=' . $apiKey . '&id_kabupaten=' . $jombangId, [
                'verify' => false
            ]);
            
            $kecamatan = json_decode($response->getBody()->getContents(), true);
            
            $cukir = null;
            foreach ($kecamatan['value'] as $kec) {
                if (stripos($kec['name'], 'cukir') !== false) {
                    $cukir = $kec;
                    echo "Cukir ID: {$cukir['id']}, Name: {$cukir['name']}\n";
                    break;
                }
            }
            
            if ($cukir) {
                $response = $client->get('https://api.binderbyte.com/wilayah/kelurahan?api_key=' . $apiKey . '&id_kecamatan=' . $cukir['id'], [
                    'verify' => false
                ]);
                
                $kelurahan = json_decode($response->getBody()->getContents(), true);
                
                echo "Kelurahan in Cukir:\n";
                foreach ($kelurahan['value'] as $kel) {
                    echo "- {$kel['id']}: {$kel['name']}\n";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>
