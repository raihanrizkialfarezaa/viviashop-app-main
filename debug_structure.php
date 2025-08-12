<?php

require_once __DIR__ . '/rajaongkir_komerce.php';

echo "=== DEBUGGING API RESPONSE STRUCTURE ===\n\n";

try {
    $rajaOngkir = new RajaOngkirKomerce();
    
    echo "Getting provinces...\n";
    $provinces = $rajaOngkir->getProvinces();
    
    echo "Provinces structure:\n";
    echo "Type: " . gettype($provinces) . "\n";
    echo "Is array: " . (is_array($provinces) ? 'Yes' : 'No') . "\n";
    
    if(is_array($provinces)) {
        echo "Count: " . count($provinces) . "\n";
        echo "First 3 items:\n";
        $count = 0;
        foreach($provinces as $key => $value) {
            if($count < 3) {
                echo "Key: $key\n";
                echo "Value type: " . gettype($value) . "\n";
                echo "Value: " . print_r($value, true) . "\n";
                echo "---\n";
                $count++;
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
