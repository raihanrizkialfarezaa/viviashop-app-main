<?php

echo "=== TESTING NEW API ENDPOINTS ===\n\n";

$baseUrl = 'http://127.0.0.1:8000';
$endpoints = [
    '/api/provinces',
    '/api/cities/18',
    '/api/districts/388'
];

foreach ($endpoints as $endpoint) {
    echo "Testing: $baseUrl$endpoint\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "HTTP Code: $httpCode\n";
    echo "Response length: " . strlen($response) . "\n";
    
    if ($httpCode == 200) {
        // Try to decode as JSON
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ Valid JSON with " . count($decoded) . " items\n";
            if (count($decoded) > 0) {
                echo "Sample: " . json_encode($decoded[0]) . "\n";
            }
        } else {
            echo "❌ Invalid JSON - " . json_last_error_msg() . "\n";
            echo "First 100 chars: " . substr($response, 0, 100) . "\n";
        }
    } else {
        echo "❌ HTTP Error - First 100 chars: " . substr($response, 0, 100) . "\n";
    }
    echo "---\n";
    
    curl_close($ch);
}

?>
