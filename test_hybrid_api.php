<?php

require_once 'vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "=== Testing Hybrid API Approach ===\n\n";

// Test 1: Binderbyte for provinces (address data)
echo "1. Testing Binderbyte API for provinces...\n";
try {
    $client = new \GuzzleHttp\Client(['verify' => false]);
    $api_key = $_ENV['RAJAONGKIR_API_KEY'];
    $base_url = $_ENV['RAJAONGKIR_BASE_URL'];
    
    echo "   Using: $base_url with key: " . substr($api_key, 0, 10) . "...\n";
    
    $url = $base_url . '/wilayah/provinsi?api_key=' . $api_key;
    $response = $client->request('GET', $url);
    $responseBody = $response->getBody()->getContents();
    $data = json_decode($responseBody, true);
    
    if (isset($data['result']) && $data['result'] === false) {
        echo "❌ Binderbyte still has rate limits: " . $data['message'] . "\n";
    } else {
        echo "✅ Binderbyte provinces working!\n";
        if (isset($data['value']) && count($data['value']) > 0) {
            echo "   Found " . count($data['value']) . " provinces\n";
            echo "   Sample: " . $data['value'][0]['nama'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Binderbyte error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Komerce.id RajaOngkir for shipping costs
echo "2. Testing Komerce.id RajaOngkir API for shipping costs...\n";
try {
    $client = new \GuzzleHttp\Client(['verify' => false]);
    $shipping_api_key = $_ENV['RAJAONGKIR_SHIPPING_API_KEY'];
    $shipping_base_url = $_ENV['RAJAONGKIR_SHIPPING_BASE_URL'];
    
    echo "   Using: $shipping_base_url with key: " . substr($shipping_api_key, 0, 10) . "...\n";
    
    $headers = [
        'key' => $shipping_api_key,
        'Content-Type' => 'application/x-www-form-urlencoded'
    ];
    
    $params = [
        'origin' => '151', // Malang
        'destination' => '23', // Jombang
        'weight' => 1000, // 1kg
        'courier' => 'jne'
    ];
    
    $response = $client->request('POST', $shipping_base_url . '/calculate/district/domestic-cost', [
        'headers' => $headers,
        'form_params' => $params
    ]);
    
    $responseBody = $response->getBody()->getContents();
    $data = json_decode($responseBody, true);
    
    if (isset($data['data']) && count($data['data']) > 0) {
        echo "✅ Komerce.id shipping costs working!\n";
        echo "   Found " . count($data['data']) . " shipping options\n";
        echo "   Sample cost: Rp " . number_format($data['data'][0]['cost']) . "\n";
    } else {
        echo "❌ No shipping cost data found\n";
        echo "   Response: " . json_encode($data) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Komerce.id error: " . $e->getMessage() . "\n";
}

echo "\n";
echo "=== Summary ===\n";
echo "- Binderbyte: For address data (provinces, cities)\n";
echo "- Komerce.id: For shipping cost calculations\n";
?>
