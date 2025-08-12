<?php
require_once 'rajaongkir_komerce.php';

echo "=== Testing RajaOngkir API Functions ===\n\n";

$rajaOngkir = new RajaOngkirKomerce();

// Test 1: Get Provinces
echo "1. Testing getProvinces():\n";
$provinces = $rajaOngkir->getProvinces();
if ($provinces) {
    echo "✓ Successfully retrieved " . count($provinces) . " provinces\n";
    echo "First 3 provinces:\n";
    for ($i = 0; $i < min(3, count($provinces)); $i++) {
        echo "   - ID: {$provinces[$i]['id']}, Name: {$provinces[$i]['name']}\n";
    }
} else {
    echo "✗ Failed to retrieve provinces\n";
}
echo "\n";

// Test 2: Get Cities for East Java (province_id = 18)
echo "2. Testing getCities(18) - East Java:\n";
$cities = $rajaOngkir->getCities(18);
if ($cities) {
    echo "✓ Successfully retrieved " . count($cities) . " cities\n";
    echo "First 3 cities:\n";
    for ($i = 0; $i < min(3, count($cities)); $i++) {
        echo "   - ID: {$cities[$i]['id']}, Name: {$cities[$i]['name']}\n";
    }
    
    // Find Jombang
    $jombangCity = null;
    foreach ($cities as $city) {
        if (strtolower($city['name']) === 'jombang') {
            $jombangCity = $city;
            break;
        }
    }
    if ($jombangCity) {
        echo "   ✓ Found Jombang: ID {$jombangCity['id']}\n";
    }
} else {
    echo "✗ Failed to retrieve cities\n";
}
echo "\n";

// Test 3: Get Districts for Jombang (city_id = 389)
echo "3. Testing getDistricts(389) - Jombang:\n";
$districts = $rajaOngkir->getDistricts(389);
if ($districts) {
    echo "✓ Successfully retrieved " . count($districts) . " districts\n";
    echo "All districts:\n";
    foreach ($districts as $district) {
        echo "   - ID: {$district['id']}, Name: {$district['name']}\n";
    }
} else {
    echo "✗ Failed to retrieve districts\n";
}
echo "\n";

// Test 4: Get Shipping Cost to district_id 3852 (Jombang)
echo "4. Testing calculateShippingCost() to district 3852:\n";
$cost = $rajaOngkir->calculateShippingCost(3852, 3852, 1000, 'jne');
if ($cost) {
    echo "✓ Successfully retrieved shipping costs:\n";
    foreach ($cost as $option) {
        echo "   - Service: {$option['service']}, Cost: " . number_format($option['cost']) . " IDR, ETD: {$option['etd']}\n";
    }
} else {
    echo "✗ Failed to retrieve shipping costs\n";
}
echo "\n";

echo "=== Test Complete ===\n";
?>
