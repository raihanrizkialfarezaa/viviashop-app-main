<?php

echo "=== TESTING NEW RAJAONGKIR API KEY ===\n\n";

require_once __DIR__ . '/rajaongkir_komerce.php';

try {
    $rajaOngkir = new RajaOngkirKomerce();
    
    echo "Testing provinces endpoint...\n";
    $provinces = $rajaOngkir->getProvinces();
    
    if (empty($provinces)) {
        echo "❌ Failed to get provinces - API key might be invalid\n";
    } else {
        echo "✅ Provinces loaded successfully: " . count($provinces) . " provinces\n";
        
        // Find Jawa Timur
        $jawaTimur = null;
        foreach ($provinces as $id => $name) {
            // Handle both string and array formats
            $provinceName = is_array($name) ? $name['name'] : $name;
            if (strpos(strtoupper($provinceName), 'JAWA TIMUR') !== false) {
                $jawaTimur = ['id' => $id, 'name' => $provinceName];
                break;
            }
        }
        
        if ($jawaTimur) {
            echo "✅ Found Jawa Timur: ID = {$jawaTimur['id']}, Name = {$jawaTimur['name']}\n\n";
            
            echo "Testing cities endpoint for Jawa Timur...\n";
            $cities = $rajaOngkir->getCities($jawaTimur['id']);
            
            if (empty($cities)) {
                echo "❌ Failed to get cities for Jawa Timur\n";
            } else {
                echo "✅ Cities loaded successfully: " . count($cities) . " cities\n";
                
                // Find Mojokerto
                $mojokerto = null;
                foreach ($cities as $id => $name) {
                    // Handle both string and array formats
                    $cityName = is_array($name) ? $name['name'] : $name;
                    if (strpos(strtoupper($cityName), 'MOJOKERTO') !== false) {
                        $mojokerto = ['id' => $id, 'name' => $cityName];
                        break;
                    }
                }
                
                if ($mojokerto) {
                    echo "✅ Found Mojokerto: ID = {$mojokerto['id']}, Name = {$mojokerto['name']}\n\n";
                    
                    echo "Testing districts endpoint for Mojokerto...\n";
                    $districts = $rajaOngkir->getDistricts($mojokerto['id']);
                    
                    if (empty($districts)) {
                        echo "❌ Failed to get districts for Mojokerto\n";
                    } else {
                        echo "✅ Districts loaded successfully: " . count($districts) . " districts\n";
                        
                        // Show first 5 districts
                        echo "Sample districts:\n";
                        $count = 0;
                        foreach ($districts as $id => $name) {
                            $districtName = is_array($name) ? $name['name'] : $name;
                            echo "- {$districtName}\n";
                            $count++;
                            if ($count >= 5) break;
                        }
                        echo "\n✅ NEW API KEY IS WORKING PERFECTLY!\n";
                    }
                } else {
                    echo "❌ Mojokerto not found in cities\n";
                }
            }
        } else {
            echo "❌ Jawa Timur not found in provinces\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error testing API: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";

?>
