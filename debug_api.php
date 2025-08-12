<?php
require_once 'rajaongkir_komerce.php';

$api = new RajaOngkirKomerce();

try {
    echo "=== Testing RajaOngkir API ===\n";
    
    // Test provinces with raw response debug
    echo "Testing getProvinces()...\n";
    $provinces = $api->getProvinces();
    echo "Raw provinces response:\n";
    print_r($provinces);
    echo "\n";
    
    // Check if response has expected structure
    if (is_array($provinces) && count($provinces) > 0) {
        echo "Provinces found: " . count($provinces) . "\n";
        
        // Look for Jawa Timur
        foreach ($provinces as $province) {
            $provinceName = $province['name'] ?? 'Unknown';
            $provinceId = $province['id'] ?? 'Unknown';
            
            if (stripos($provinceName, 'jawa timur') !== false || stripos($provinceName, 'east java') !== false) {
                echo "Found Jawa Timur: " . $provinceName . " (ID: " . $provinceId . ")\n";
                
                // Test cities
                echo "\nTesting getCities(" . $provinceId . ")...\n";
                $cities = $api->getCities($provinceId);
                echo "Raw cities response (count: " . count($cities) . "):\n";
                
                if (is_array($cities) && count($cities) > 0) {
                    echo "\nCities found: " . count($cities) . "\n";
                    
                    // Find Jombang or use first city
                    $testCityId = null;
                    $testCityName = '';
                    
                    foreach ($cities as $city) {
                        $cityName = $city['name'] ?? 'Unknown';
                        $cityId = $city['id'] ?? 'Unknown';
                        
                        if (stripos($cityName, 'jombang') !== false) {
                            $testCityId = $cityId;
                            $testCityName = $cityName;
                            break;
                        }
                    }
                    
                    // If Jombang not found, use first city
                    if (!$testCityId) {
                        $firstCity = $cities[0];
                        $testCityId = $firstCity['id'] ?? 'Unknown';
                        $testCityName = $firstCity['name'] ?? 'Unknown';
                    }
                    
                    echo "Testing with city: " . $testCityName . " (ID: " . $testCityId . ")\n";
                    
                    // Test districts
                    echo "\nTesting getDistricts(" . $testCityId . ")...\n";
                    $districts = $api->getDistricts($testCityId);
                    echo "Raw districts response (count: " . count($districts) . "):\n";
                    
                    if (is_array($districts) && count($districts) > 0) {
                        echo "\nDistricts found: " . count($districts) . "\n";
                        
                        $firstDistrict = $districts[0];
                        $districtId = $firstDistrict['id'] ?? 'Unknown';
                        $districtName = $firstDistrict['name'] ?? 'Unknown';
                        
                        echo "Testing shipping with district: " . $districtName . " (ID: " . $districtId . ")\n";
                        
                        // Test shipping calculation
                        echo "\nTesting calculateShippingCost(3852, " . $districtId . ", 1000, 'jne')...\n";
                        $shipping = $api->calculateShippingCost(3852, $districtId, 1000, 'jne');
                        echo "Raw shipping response (count: " . count($shipping) . "):\n";
                        
                        if (count($shipping) > 0) {
                            echo "Sample shipping option:\n";
                            print_r($shipping[0]);
                        }
                    }
                }
                break;
            }
        }
    } else {
        echo "ERROR: Unexpected provinces response structure\n";
        echo "Response keys: " . implode(', ', array_keys($provinces)) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
