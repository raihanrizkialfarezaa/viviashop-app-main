<?php

require_once 'vendor/autoload.php';

// Test Binderbyte API response format
$client = new \GuzzleHttp\Client(['verify' => false]);
$api_key = 'baf168a52b7282eb517d49ced902ec2bf06e0d5362b63d14aa6aeb9d993f5228';
$base_url = 'https://api.binderbyte.com';

try {
    // Test provinsi endpoint
    $url = $base_url . '/wilayah/provinsi?api_key=' . $api_key;
    $response = $client->request('GET', $url);
    $responseBody = $response->getBody()->getContents();
    $data = json_decode($responseBody, true);
    
    echo "Provinsi response format:\n";
    print_r($data);
    echo "\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Response body (if available): " . (isset($responseBody) ? $responseBody : 'N/A') . "\n";
}
?>
