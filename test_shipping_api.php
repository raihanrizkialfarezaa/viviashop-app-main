<?php

require_once 'rajaongkir_komerce.php';

$rajaOngkir = new RajaOngkirKomerce();

echo "Testing shipping cost calculation...\n";

$origin = 3852; // Jombang District ID
$destination = 3850; // Test destination
$weight = 1000; // 1kg in grams
$courier = 'jne';

echo "Origin: $origin\n";
echo "Destination: $destination\n";
echo "Weight: $weight grams\n";
echo "Courier: $courier\n\n";

try {
    $result = $rajaOngkir->calculateShippingCost($origin, $destination, $weight, $courier);
    
    echo "Raw API Response:\n";
    print_r($result);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
