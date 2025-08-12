<?php
// Quick test for Mojokerto districts
require_once 'rajaongkir_komerce.php';

echo "Testing Mojokerto Districts API\n";
echo "================================\n\n";

try {
    $rajaOngkir = new RajaOngkirKomerce();
    
    // First, let's find the correct province ID for Jawa Timur
    echo "1. Getting all provinces to find Jawa Timur:\n";
    $provinces = $rajaOngkir->getProvinces();
    
    $jatim_id = null;
    if (is_array($provinces)) {
        foreach($provinces as $id => $provinceName) {
            $nameStr = is_array($provinceName) ? ($provinceName['name'] ?? $provinceName['province'] ?? $provinceName) : $provinceName;
            $nameStr = is_string($nameStr) ? $nameStr : (string)$nameStr;
            
            if(stripos($nameStr, 'jawa timur') !== false || stripos($nameStr, 'east java') !== false) {
                echo "   Found Jawa Timur: ID=$id, Name=$nameStr\n";
                $jatim_id = $id;
                break;
            }
        }
    }
    
    if (!$jatim_id) {
        echo "ERROR: Could not find Jawa Timur province ID\n";
        echo "Available provinces:\n";
        foreach($provinces as $id => $name) {
            $nameStr = is_array($name) ? ($name['name'] ?? $name['province'] ?? $name) : $name;
            echo "   ID=$id, Name=$nameStr\n";
        }
        return;
    }
    
    // Get Jawa Timur cities to find Mojokerto
    echo "\n2. Getting cities in Jawa Timur (Province ID: $jatim_id):\n";
    $cities = $rajaOngkir->getCities($jatim_id);
    
    echo "Cities data structure:\n";
    var_dump($cities);
    echo "\n";
    
    $mojokerto_ids = [];
    if (is_array($cities)) {
        foreach($cities as $id => $cityData) {
            // Handle if cityData is array or string
            $name = is_array($cityData) ? ($cityData['name'] ?? $cityData['city_name'] ?? $cityData) : $cityData;
            $nameStr = is_string($name) ? $name : (string)$name;
            
            if(stripos($nameStr, 'mojokerto') !== false) {
                echo "   Found: ID=$id, Name=$nameStr\n";
                $mojokerto_ids[] = $id;
            }
        }
    }
    
    echo "\n3. Getting districts for each Mojokerto:\n";
    foreach($mojokerto_ids as $city_id) {
        echo "   Testing City ID: $city_id\n";
        $districts = $rajaOngkir->getDistricts($city_id);
        
        if(is_array($districts) && count($districts) > 0) {
            echo "   SUCCESS: Found " . count($districts) . " districts:\n";
            $count = 0;
            foreach($districts as $dist_id => $dist_name) {
                if($count < 5) { // Show first 5
                    echo "     - ID=$dist_id, Name=$dist_name\n";
                }
                $count++;
            }
            if($count > 5) {
                echo "     ... and " . ($count - 5) . " more districts\n";
            }
        } else {
            echo "   ERROR: No districts found or invalid response\n";
            print_r($districts);
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
