<?php

require_once __DIR__ . '/bootstrap/app.php';

$app = \Illuminate\Foundation\Application::getInstance();
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

require_once __DIR__ . '/rajaongkir_komerce.php';

echo "=== CHECKING CITY IDs ===\n\n";

try {
    $rajaOngkir = new RajaOngkirKomerce();
    $cities = $rajaOngkir->getCities(11); // Jawa Timur
    
    echo "Looking for cities 290 and 388...\n\n";
    
    foreach($cities as $id => $name) {
        if($id == 290) {
            echo "City ID 290 = $name\n";
        }
        if($id == 388) {
            echo "City ID 388 = $name\n";
        }
    }
    
    echo "\n=== CONCLUSION ===\n";
    echo "User currently has city_id: 290\n";
    echo "User should have city_id: 388 for Mojokerto\n";
    echo "Need to update user's city_id in database!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>
