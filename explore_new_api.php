<?php

echo "=== EXPLORING NEW API STRUCTURE ===\n\n";

require_once __DIR__ . '/rajaongkir_komerce.php';

try {
    $rajaOngkir = new RajaOngkirKomerce();
    
    echo "All provinces:\n";
    $provinces = $rajaOngkir->getProvinces();
    foreach ($provinces as $id => $name) {
        $provinceName = is_array($name) ? $name['name'] : $name;
        if (strpos(strtoupper($provinceName), 'JAWA') !== false) {
            echo "- ID: {$id}, Name: {$provinceName}\n";
        }
    }
    
    echo "\nCities in Jawa Timur (ID: 17):\n";
    $cities = $rajaOngkir->getCities(17);
    foreach ($cities as $id => $name) {
        $cityName = is_array($name) ? $name['name'] : $name;
        echo "- ID: {$id}, Name: {$cityName}\n";
    }
    
    // Check if there are other Jawa provinces
    foreach ($provinces as $id => $name) {
        $provinceName = is_array($name) ? $name['name'] : $name;
        if (strpos(strtoupper($provinceName), 'JAWA') !== false && $id != 17) {
            echo "\nChecking {$provinceName} (ID: {$id}) for Mojokerto:\n";
            $cities = $rajaOngkir->getCities($id);
            foreach ($cities as $cityId => $cityName) {
                $cityNameStr = is_array($cityName) ? $cityName['name'] : $cityName;
                if (strpos(strtoupper($cityNameStr), 'MOJOKERTO') !== false) {
                    echo "✅ Found Mojokerto: ID = {$cityId}, Name = {$cityNameStr}\n";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
