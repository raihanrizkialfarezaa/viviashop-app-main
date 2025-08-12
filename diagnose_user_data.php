<?php
// Check current user data vs correct API data
require_once 'rajaongkir_komerce.php';

$rajaOngkir = new RajaOngkirKomerce();

echo "=== CHECKING CORRECT IDs vs USER DATA ===\n\n";

// 1. Find correct Jawa Timur ID
$provinces = $rajaOngkir->getProvinces();
$correctJatimId = null;
foreach($provinces as $province) {
    if(stripos($province['name'], 'JAWA TIMUR') !== false) {
        $correctJatimId = $province['id'];
        echo "✅ CORRECT Jawa Timur ID: {$province['id']} - {$province['name']}\n";
        break;
    }
}

// 2. Find correct Mojokerto ID
if($correctJatimId) {
    $cities = $rajaOngkir->getCities($correctJatimId);
    $correctMojokertoId = null;
    foreach($cities as $city) {
        if(stripos($city['name'], 'MOJOKERTO') !== false) {
            $correctMojokertoId = $city['id'];
            echo "✅ CORRECT Mojokerto ID: {$city['id']} - {$city['name']}\n";
            break;
        }
    }
    
    // 3. Get correct districts for Mojokerto
    if($correctMojokertoId) {
        $districts = $rajaOngkir->getDistricts($correctMojokertoId);
        echo "✅ CORRECT Districts for Mojokerto (first 5):\n";
        foreach(array_slice($districts, 0, 5) as $district) {
            echo "   - ID: {$district['id']} - {$district['name']}\n";
        }
    }
}

echo "\n=== PROBLEM DIAGNOSIS ===\n";
echo "If user's profile shows 'Alak' district, it means:\n";
echo "1. User's province_id or city_id is WRONG in database\n";
echo "2. User might have old IDs from different API mapping\n";
echo "3. Need to update user data with correct IDs\n";

// 4. Check what district ID 'Alak' might be from
echo "\n=== SEARCHING FOR 'ALAK' DISTRICT ===\n";
$foundAlak = false;

// Check some major cities for Alak
$testCities = [388, 389, 390, 391, 392]; // Mojokerto and surrounding areas
foreach($testCities as $cityId) {
    try {
        $testDistricts = $rajaOngkir->getDistricts($cityId);
        foreach($testDistricts as $district) {
            if(stripos($district['name'], 'ALAK') !== false) {
                echo "❌ Found 'Alak' in City ID: $cityId, District: {$district['name']}\n";
                $foundAlak = true;
            }
        }
    } catch(Exception $e) {
        // Skip if city doesn't exist
    }
}

if(!$foundAlak) {
    echo "❌ 'Alak' not found in tested cities - user has wrong city_id!\n";
}
