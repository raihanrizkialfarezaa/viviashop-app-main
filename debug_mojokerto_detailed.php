<?php
// Debug specific untuk Mojokerto districts
require_once 'rajaongkir_komerce.php';

echo "=== DEBUG MOJOKERTO DISTRICTS ===\n\n";

try {
    $rajaOngkir = new RajaOngkirKomerce();
    
    // 1. Cari semua provinces untuk Jawa Timur
    echo "1. Mencari Province Jawa Timur:\n";
    $provinces = $rajaOngkir->getProvinces();
    $jatimId = null;
    
    echo "   Structure provinces:\n";
    var_dump(array_slice($provinces, 0, 3, true)); // Show first 3 elements
    
    foreach($provinces as $id => $provinceData) {
        $name = is_array($provinceData) ? ($provinceData['name'] ?? $provinceData['province'] ?? '') : $provinceData;
        if(stripos($name, 'JAWA TIMUR') !== false) {
            echo "   Found: ID=$id, Name=$name\n";
            $jatimId = $id;
            break;
        }
    }
    
    if(!$jatimId) {
        echo "ERROR: Jawa Timur tidak ditemukan!\n";
        return;
    }
    
    // 2. Cari semua cities di Jawa Timur untuk Mojokerto
    echo "\n2. Mencari Cities di Jawa Timur (ID: $jatimId):\n";
    $cities = $rajaOngkir->getCities($jatimId);
    $mojokertoIds = [];
    
    foreach($cities as $city) {
        $cityName = $city['name'] ?? $city['city_name'] ?? '';
        if(stripos($cityName, 'MOJOKERTO') !== false) {
            echo "   Found Mojokerto: ID={$city['id']}, Name=$cityName\n";
            $mojokertoIds[] = $city['id'];
        }
    }
    
    if(empty($mojokertoIds)) {
        echo "   Mojokerto tidak ditemukan! Menampilkan 10 cities pertama:\n";
        $count = 0;
        foreach($cities as $city) {
            if($count >= 10) break;
            $cityName = $city['name'] ?? $city['city_name'] ?? '';
            echo "   ID={$city['id']}, Name=$cityName\n";
            $count++;
        }
        return;
    }
    
    // 3. Test districts untuk setiap Mojokerto yang ditemukan
    foreach($mojokertoIds as $cityId) {
        echo "\n3. Testing Districts untuk City ID: $cityId\n";
        $districts = $rajaOngkir->getDistricts($cityId);
        
        echo "   Raw API Response:\n";
        print_r($districts);
        
        if(is_array($districts) && !empty($districts)) {
            echo "\n   Formatted Districts:\n";
            foreach($districts as $district) {
                $districtId = $district['subdistrict_id'] ?? $district['id'] ?? 'N/A';
                $districtName = $district['subdistrict_name'] ?? $district['name'] ?? 'N/A';
                echo "   - ID: $districtId, Name: $districtName\n";
            }
        } else {
            echo "   ERROR: No districts found or invalid response\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
