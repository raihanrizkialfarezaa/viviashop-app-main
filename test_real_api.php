<?php
// Test direct API call seperti di web
require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Simulate request
$request = Illuminate\Http\Request::create('/orders/provinces', 'GET');
$response = $kernel->handle($request);

echo "=== PROVINCES API RESPONSE ===\n";
echo $response->getContent();
echo "\n\n";

// Test cities for province 17 (yang katanya Jawa Timur)
$request2 = Illuminate\Http\Request::create('/orders/cities/17', 'GET');
$response2 = $kernel->handle($request2);

echo "=== CITIES API RESPONSE FOR PROVINCE 17 ===\n";
echo $response2->getContent();
echo "\n\n";

// Test cari province yang bener untuk Jawa Timur
$provinceData = json_decode($response->getContent(), true);
echo "=== SEARCHING FOR REAL JAWA TIMUR ===\n";
if (is_array($provinceData)) {
    foreach($provinceData as $prov) {
        if (isset($prov['name']) && stripos($prov['name'], 'JAWA TIMUR') !== false) {
            echo "Found real Jawa Timur: ID={$prov['id']}, Name={$prov['name']}\n";
            
            // Test cities for the real Jawa Timur
            $request3 = Illuminate\Http\Request::create('/orders/cities/' . $prov['id'], 'GET');
            $response3 = $kernel->handle($request3);
            
            echo "\n=== CITIES FOR REAL JAWA TIMUR (ID: {$prov['id']}) ===\n";
            $citiesData = json_decode($response3->getContent(), true);
            
            if (is_array($citiesData)) {
                foreach($citiesData as $city) {
                    if (isset($city['name']) && stripos($city['name'], 'MOJOKERTO') !== false) {
                        echo "Found Mojokerto: ID={$city['id']}, Name={$city['name']}\n";
                        
                        // Test districts for Mojokerto
                        $request4 = Illuminate\Http\Request::create('/orders/districts/' . $city['id'], 'GET');
                        $response4 = $kernel->handle($request4);
                        
                        echo "\n=== DISTRICTS FOR MOJOKERTO (ID: {$city['id']}) ===\n";
                        echo $response4->getContent();
                        break;
                    }
                }
            }
            break;
        }
    }
}
