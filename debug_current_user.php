<?php
// Simple debug script to check current user data
require_once 'vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\Frontend\OrderController;

// Create OrderController instance
$controller = new OrderController();

echo "=== DEBUG USER DATA AND API ===\n\n";

// Test user data (simulate what's in profile page)
echo "Current User Data (simulated):\n";
echo "Province ID: 18 (Jawa Timur)\n";
echo "City ID: 388 (Mojokerto)\n";
echo "District ID: (to be verified)\n\n";

// Test Jawa Timur cities
echo "Testing Jawa Timur (ID: 18) cities:\n";
try {
    $cities = $controller->cities(18);
    $cityData = $cities->getData(true);
    
    // Find Mojokerto
    $mojokerto = null;
    foreach($cityData as $city) {
        if(strpos(strtoupper($city['name']), 'MOJOKERTO') !== false) {
            $mojokerto = $city;
            break;
        }
    }
    
    if($mojokerto) {
        echo "✅ Found Mojokerto: ID=" . $mojokerto['id'] . ", Name=" . $mojokerto['name'] . "\n";
    } else {
        echo "❌ Mojokerto not found in Jawa Timur cities\n";
    }
} catch (Exception $e) {
    echo "❌ Error getting cities: " . $e->getMessage() . "\n";
}

echo "\n";

// Test Mojokerto districts
echo "Testing Mojokerto (ID: 388) districts:\n";
try {
    $districts = $controller->districts(388);
    $districtData = $districts->getData(true);
    
    echo "Districts found: " . count($districtData) . "\n";
    
    // Check for ALAK (wrong) and BANGSAL (correct)
    $hasAlak = false;
    $hasBangsal = false;
    $sampleDistricts = [];
    
    foreach($districtData as $district) {
        if(strpos(strtoupper($district['name']), 'ALAK') !== false) {
            $hasAlak = true;
        }
        if(strpos(strtoupper($district['name']), 'BANGSAL') !== false) {
            $hasBangsal = true;
        }
        
        // Store first 5 districts as sample
        if(count($sampleDistricts) < 5) {
            $sampleDistricts[] = $district['name'];
        }
    }
    
    echo "Sample districts: " . implode(', ', $sampleDistricts) . "\n";
    echo "Has ALAK (wrong): " . ($hasAlak ? 'YES' : 'NO') . "\n";
    echo "Has BANGSAL (correct): " . ($hasBangsal ? 'YES' : 'NO') . "\n";
    
    if(!$hasAlak && $hasBangsal) {
        echo "✅ CORRECT! This is Mojokerto data\n";
    } else {
        echo "❌ WRONG! This is NOT Mojokerto data\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error getting districts: " . $e->getMessage() . "\n";
}

echo "\n=== END DEBUG ===\n";
?>
