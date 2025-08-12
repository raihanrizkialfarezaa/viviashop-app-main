<?php

require_once __DIR__ . '/rajaongkir_komerce.php';

echo "=== TESTING FIXED RAJAONGKIR KOMERCE ===\n\n";

try {
    $rajaOngkir = new RajaOngkirKomerce();
    
    echo "1. Testing getProvinces()...\n";
    $provinces = $rajaOngkir->getProvinces();
    
    echo "Provinces type: " . gettype($provinces) . "\n";
    echo "Provinces count: " . count($provinces) . "\n";
    
    // Find Jawa Timur
    $jawaTimurId = null;
    foreach($provinces as $id => $name) {
        if(stripos($name, 'JAWA TIMUR') !== false) {
            $jawaTimurId = $id;
            echo "✅ Found Jawa Timur: ID = $id, Name = $name\n";
            break;
        }
    }
    
    if($jawaTimurId) {
        echo "\n2. Testing getCities($jawaTimurId)...\n";
        $cities = $rajaOngkir->getCities($jawaTimurId);
        
        echo "Cities type: " . gettype($cities) . "\n";
        echo "Cities count: " . count($cities) . "\n";
        
        // Find Mojokerto
        $mojokertoId = null;
        foreach($cities as $id => $name) {
            if(stripos($name, 'MOJOKERTO') !== false) {
                $mojokertoId = $id;
                echo "✅ Found Mojokerto: ID = $id, Name = $name\n";
                break;
            }
        }
        
        // Show first 10 cities to verify they're correct
        echo "\nFirst 10 cities in Jawa Timur:\n";
        $count = 0;
        foreach($cities as $id => $name) {
            if($count < 10) {
                echo "- ID: $id, Name: $name\n";
                $count++;
            }
        }
        
        if($mojokertoId) {
            echo "\n3. Testing getDistricts($mojokertoId)...\n";
            $districts = $rajaOngkir->getDistricts($mojokertoId);
            
            echo "Districts type: " . gettype($districts) . "\n";
            echo "Districts count: " . count($districts) . "\n";
            
            echo "First 10 districts in Mojokerto:\n";
            $count = 0;
            foreach($districts as $id => $name) {
                if($count < 10) {
                    echo "- ID: $id, Name: $name\n";
                    $count++;
                }
            }
        }
    }
    
    echo "\n✅ API key is working correctly!\n";
    echo "Final IDs:\n";
    echo "- Jawa Timur: $jawaTimurId\n";
    echo "- Mojokerto: " . ($mojokertoId ?? 'Not found') . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
