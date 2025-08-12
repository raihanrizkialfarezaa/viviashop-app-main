<?php

echo "=== CHECKING ENDPOINT RESPONSES ===\n\n";

$endpoints = [
    'http://127.0.0.1:8000/orders/provinces',
    'http://127.0.0.1:8000/orders/cities/18',
    'http://127.0.0.1:8000/orders/districts/388'
];

foreach($endpoints as $url) {
    echo "Testing: $url\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    echo "Response length: " . strlen($response) . "\n";
    echo "First 200 chars: " . substr($response, 0, 200) . "\n";
    echo "---\n";
}

?>
