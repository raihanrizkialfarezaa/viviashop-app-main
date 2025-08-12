<?php
echo "=== Testing API Endpoints After Fix ===\n\n";

require_once 'rajaongkir_komerce.php';
$rajaOngkir = new RajaOngkirKomerce();

// Test API directly
echo "1. Direct API Test - Get Districts for Jombang (389):\n";
$districts = $rajaOngkir->getDistricts(389);
if ($districts) {
    echo "✓ Found " . count($districts) . " districts\n";
    echo "Sample districts:\n";
    foreach (array_slice($districts, 0, 3) as $district) {
        echo "   - ID: {$district['id']}, Name: {$district['name']}\n";
    }
} else {
    echo "✗ No districts found\n";
}
echo "\n";

// Test cities for East Java
echo "2. Direct API Test - Get Cities for East Java (18):\n";
$cities = $rajaOngkir->getCities(18);
if ($cities) {
    echo "✓ Found " . count($cities) . " cities\n";
    echo "Sample cities:\n";
    foreach (array_slice($cities, 0, 3) as $city) {
        echo "   - ID: {$city['id']}, Name: {$city['name']}\n";
    }
} else {
    echo "✗ No cities found\n";
}
echo "\n";

echo "=== Test Complete ===\n";
?>
