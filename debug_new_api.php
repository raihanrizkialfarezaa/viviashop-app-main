<?php

require_once __DIR__ . '/rajaongkir_komerce.php';

echo "=== DEBUGGING NEW API KEY PROVINCES ===\n\n";

try {
    $rajaOngkir = new RajaOngkirKomerce();
    
    echo "Getting all provinces with new API key...\n\n";
    $provinces = $rajaOngkir->getProvinces();
    
    // Find Jawa Timur
    $jawaTimurId = null;
    foreach($provinces as $id => $name) {
        if(stripos($name, 'JAWA TIMUR') !== false || stripos($name, 'EAST JAVA') !== false) {
            $jawaTimurId = $id;
            echo "✅ FOUND JAWA TIMUR: ID = $id, Name = $name\n\n";
            break;
        }
    }
    
    if($jawaTimurId) {
        echo "Testing cities for Jawa Timur (ID: $jawaTimurId)...\n";
        $cities = $rajaOngkir->getCities($jawaTimurId);
        
        echo "First 10 cities in Jawa Timur:\n";
        $count = 0;
        foreach($cities as $id => $name) {
            if($count < 10) {
                echo "- ID: $id, Name: $name\n";
                $count++;
            }
        }
        
        echo "\nLooking for Mojokerto...\n";
        $mojokerto = null;
        foreach($cities as $id => $name) {
            if(stripos($name, 'MOJOKERTO') !== false) {
                $mojokerto = ['id' => $id, 'name' => $name];
                echo "✅ FOUND MOJOKERTO: ID = $id, Name = $name\n";
                break;
            }
        }
        
        if($mojokerto) {
            echo "\nTesting districts for Mojokerto (ID: {$mojokerto['id']})...\n";
            $districts = $rajaOngkir->getDistricts($mojokerto['id']);
            
            echo "First 5 districts in Mojokerto:\n";
            $count = 0;
            foreach($districts as $id => $name) {
                if($count < 5) {
                    echo "- ID: $id, Name: $name\n";
                    $count++;
                }
            }
        } else {
            echo "❌ MOJOKERTO NOT FOUND!\n";
        }
        
    } else {
        echo "❌ JAWA TIMUR NOT FOUND!\n";
        echo "Available provinces:\n";
        foreach($provinces as $id => $name) {
            echo "- ID: $id, Name: $name\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
