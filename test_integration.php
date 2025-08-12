<?php
// Test script to verify RajaOngkir Komerce integration

require_once __DIR__ . '/rajaongkir_komerce.php';

try {
    $rajaOngkir = new RajaOngkirKomerce();
    
    echo "=== Testing RajaOngkir Komerce Integration ===\n\n";
    
    // Test 1: Get Provinces
    echo "1. Testing getProvinces():\n";
    $provinces = $rajaOngkir->getProvinces();
    echo "Provinces count: " . count($provinces) . "\n";
    echo "Jawa Timur found: " . (array_search('Jawa Timur', array_column($provinces, 'name')) !== false ? 'Yes' : 'No') . "\n\n";
    
    // Test 2: Get Cities for Jawa Timur (ID: 18)
    echo "2. Testing getCities(18) for Jawa Timur:\n";
    $cities = $rajaOngkir->getCities(18);
    echo "Cities in Jawa Timur count: " . count($cities) . "\n";
    $jombangKey = array_search('Jombang', array_column($cities, 'name'));
    echo "Jombang found: " . ($jombangKey !== false ? 'Yes (ID: ' . $cities[$jombangKey]['id'] . ')' : 'No') . "\n\n";
    
    // Test 3: Get Districts for Jombang (ID: 389)
    echo "3. Testing getDistricts(389) for Jombang:\n";
    $districts = $rajaOngkir->getDistricts(389);
    echo "Districts in Jombang count: " . count($districts) . "\n";
    $cukirKey = array_search('Cukir', array_column($districts, 'name'));
    echo "Cukir found: " . ($cukirKey !== false ? 'Yes (ID: ' . $districts[$cukirKey]['id'] . ')' : 'No') . "\n\n";
    
    // Test 4: Calculate shipping cost from Cukir (3852) to another district
    echo "4. Testing calculateShippingCost(3852, 3852, 1000, 'jne'):\n";
    $shippingOptions = $rajaOngkir->calculateShippingCost(3852, 3852, 1000, 'jne');
    echo "Shipping options count: " . count($shippingOptions) . "\n";
    if (!empty($shippingOptions)) {
        echo "Sample option: " . $shippingOptions[0]['service'] . " - Rp. " . number_format($shippingOptions[0]['cost']) . " (" . $shippingOptions[0]['etd'] . ")\n";
    }
    
    echo "\n=== Integration Test Complete ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
