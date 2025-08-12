<?php
// Test direct API endpoint untuk Mojokerto
require_once 'bootstrap/app.php';

// Initialize Laravel app
$app = require_once 'bootstrap/app.php';

// Set up environment
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SERVER_NAME'] = 'localhost';

// Initialize the app
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "=== DIRECT API TEST FOR MOJOKERTO ===\n\n";

// 1. Test Province endpoint
echo "1. Testing /orders/provinces:\n";
try {
    $request1 = Illuminate\Http\Request::create('/orders/provinces', 'GET');
    $response1 = $kernel->handle($request1);
    $provincesData = json_decode($response1->getContent(), true);
    
    $jatimId = null;
    foreach($provincesData as $province) {
        if(stripos($province['name'], 'JAWA TIMUR') !== false) {
            $jatimId = $province['id'];
            echo "   ✅ Found Jawa Timur: ID = $jatimId\n";
            break;
        }
    }
    
    if(!$jatimId) {
        echo "   ❌ Jawa Timur not found!\n";
        exit;
    }
} catch(Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit;
}

// 2. Test Cities endpoint for Jawa Timur
echo "\n2. Testing /orders/cities/$jatimId:\n";
try {
    $request2 = Illuminate\Http\Request::create("/orders/cities/$jatimId", 'GET');
    $response2 = $kernel->handle($request2);
    $citiesData = json_decode($response2->getContent(), true);
    
    $mojokertoId = null;
    foreach($citiesData as $city) {
        if(stripos($city['name'], 'MOJOKERTO') !== false) {
            $mojokertoId = $city['id'];
            echo "   ✅ Found Mojokerto: ID = $mojokertoId\n";
            break;
        }
    }
    
    if(!$mojokertoId) {
        echo "   ❌ Mojokerto not found in Jawa Timur cities!\n";
        echo "   Available cities:\n";
        foreach(array_slice($citiesData, 0, 10) as $city) {
            echo "     - ID: {$city['id']}, Name: {$city['name']}\n";
        }
        exit;
    }
} catch(Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    exit;
}

// 3. Test Districts endpoint for Mojokerto - THIS IS THE CRITICAL TEST
echo "\n3. Testing /orders/districts/$mojokertoId:\n";
try {
    $request3 = Illuminate\Http\Request::create("/orders/districts/$mojokertoId", 'GET');
    $response3 = $kernel->handle($request3);
    $districtsData = json_decode($response3->getContent(), true);
    
    echo "   HTTP Status: " . $response3->getStatusCode() . "\n";
    echo "   Response Content: " . $response3->getContent() . "\n";
    
    if(is_array($districtsData) && !empty($districtsData)) {
        echo "   ✅ Found " . count($districtsData) . " districts:\n";
        foreach(array_slice($districtsData, 0, 10) as $district) {
            echo "     - ID: {$district['id']}, Name: {$district['name']}\n";
        }
        
        // Check if 'ALAK' is in this list
        $foundAlak = false;
        foreach($districtsData as $district) {
            if(stripos($district['name'], 'ALAK') !== false) {
                echo "\n   ❌ WARNING: Found 'ALAK' in Mojokerto districts!\n";
                echo "       This means wrong city_id is being used!\n";
                $foundAlak = true;
            }
        }
        
        if(!$foundAlak) {
            echo "\n   ✅ Good: No 'ALAK' found in districts (correct!)\n";
        }
        
    } else {
        echo "   ❌ No districts found or invalid response!\n";
    }
    
} catch(Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== CONCLUSION ===\n";
echo "If this test shows correct districts for Mojokerto but profile page shows wrong ones,\n";
echo "then the problem is in the frontend JavaScript or user's stored city_id.\n";
