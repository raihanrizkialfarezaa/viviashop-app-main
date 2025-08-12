<?php

echo "=== TESTING PROVINCES ENDPOINT DIRECTLY ===\n\n";

// Test direct class call
require_once 'rajaongkir_komerce.php';

try {
    $rajaOngkir = new RajaOngkirKomerce();
    $provinces = $rajaOngkir->getProvinces();
    
    echo "Direct class call result:\n";
    echo "Count: " . count($provinces) . "\n";
    echo "First few provinces:\n";
    foreach (array_slice($provinces, 0, 5) as $id => $name) {
        echo "ID: $id, Name: $name\n";
    }
    echo "\n";
    
    // Test as JSON
    echo "JSON format:\n";
    $jsonProvinces = [];
    foreach ($provinces as $id => $name) {
        $jsonProvinces[] = ['id' => $id, 'name' => $name];
    }
    echo json_encode(array_slice($jsonProvinces, 0, 3)) . "\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test endpoint manually
echo "=== TESTING HTTP ENDPOINT ===\n";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'header' => "User-Agent: PHP Test\r\n"
    ]
]);

$response = file_get_contents('http://127.0.0.1:8000/orders/provinces', false, $context);
if ($response !== false) {
    echo "Response length: " . strlen($response) . "\n";
    echo "First 500 chars:\n";
    echo substr($response, 0, 500) . "\n";
    
    // Check if it's JSON
    $decoded = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "\nValid JSON response with " . count($decoded) . " items\n";
    } else {
        echo "\nNot valid JSON - Error: " . json_last_error_msg() . "\n";
    }
} else {
    echo "Failed to get response\n";
}

?>
