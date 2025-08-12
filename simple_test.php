<?php
// Simple test untuk understand API response structure
require_once 'rajaongkir_komerce.php';

$rajaOngkir = new RajaOngkirKomerce();

echo "=== TEST 1: GET ALL PROVINCES ===\n";
$provinces = $rajaOngkir->getProvinces();
echo "Total provinces: " . count($provinces) . "\n";
echo "First 5 provinces:\n";
foreach(array_slice($provinces, 0, 5) as $index => $province) {
    echo "Index $index: ";
    print_r($province);
}

// Cari Jawa Timur yang benar
echo "\n=== TEST 2: FIND JAWA TIMUR ===\n";
$jatimId = null;
foreach($provinces as $index => $province) {
    if(isset($province['name']) && stripos($province['name'], 'JAWA TIMUR') !== false) {
        echo "Found Jawa Timur at index $index: ";
        print_r($province);
        $jatimId = $province['id'];
        break;
    }
}

if($jatimId) {
    echo "\n=== TEST 3: GET CITIES FOR JAWA TIMUR (ID: $jatimId) ===\n";
    $cities = $rajaOngkir->getCities($jatimId);
    echo "Total cities: " . count($cities) . "\n";
    
    // Cari Mojokerto
    echo "Looking for Mojokerto:\n";
    foreach($cities as $index => $city) {
        if(isset($city['name']) && stripos($city['name'], 'MOJOKERTO') !== false) {
            echo "Found Mojokerto at index $index: ";
            print_r($city);
            
            // Test districts untuk Mojokerto ini
            echo "\n=== TEST 4: GET DISTRICTS FOR MOJOKERTO (ID: {$city['id']}) ===\n";
            $districts = $rajaOngkir->getDistricts($city['id']);
            echo "Total districts: " . count($districts) . "\n";
            echo "First 10 districts:\n";
            foreach(array_slice($districts, 0, 10) as $i => $district) {
                echo "District $i: ";
                print_r($district);
            }
            break;
        }
    }
}
