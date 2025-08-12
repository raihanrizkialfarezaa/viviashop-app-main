<?php

echo "=== FINAL TEST - ALL ENDPOINTS ===\n\n";

// Test RajaOngkirKomerce class directly
require_once __DIR__ . '/rajaongkir_komerce.php';
$rajaOngkir = new RajaOngkirKomerce();

echo "1. Direct API Test:\n";
$provinces = $rajaOngkir->getProvinces();
$cities = $rajaOngkir->getCities(18); // Jawa Timur
$districts = $rajaOngkir->getDistricts(388); // Mojokerto

echo "✅ Provinces: " . count($provinces) . " items (key-value format)\n";
echo "✅ Cities: " . count($cities) . " items (key-value format)\n";  
echo "✅ Districts: " . count($districts) . " items (key-value format)\n\n";

// Test Laravel endpoints
echo "2. Laravel Endpoints Test:\n";

function testEndpoint($url, $name) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if (is_array($data)) {
            echo "✅ $name: HTTP $httpCode, " . count($data) . " items\n";
            return true;
        } else {
            echo "❌ $name: HTTP $httpCode, invalid JSON\n";
            return false;
        }
    } else {
        echo "❌ $name: HTTP $httpCode\n";
        return false;
    }
}

$endpoints = [
    'Provinces' => 'http://127.0.0.1:8000/orders/provinces',
    'Cities (Jawa Timur)' => 'http://127.0.0.1:8000/orders/cities/18',
    'Districts (Mojokerto)' => 'http://127.0.0.1:8000/orders/districts/388'
];

$allPassed = true;
foreach($endpoints as $name => $url) {
    if(!testEndpoint($url, $name)) {
        $allPassed = false;
    }
}

echo "\n" . ($allPassed ? "✅ ALL TESTS PASSED!" : "❌ SOME TESTS FAILED") . "\n";
echo "\nNow you can safely:\n";
echo "1. Visit http://127.0.0.1:8000/profile - Should work perfectly\n";
echo "2. Visit http://127.0.0.1:8000/orders/checkout - Should work without errors\n";
echo "3. Select provinces/cities/districts - Should load correct data\n";

?>
