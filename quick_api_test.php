<?php
require_once 'vendor/autoload.php';
use GuzzleHttp\Client;

$client = new Client(['verify' => false]);

try {
    $response = $client->get('https://rajaongkir.komerce.id/api/v1/cost', [
        'headers' => ['key' => 'mX8UOUC63dc7a4d50f35001eVcaMa8te'],
        'query' => [
            'origin' => '273',
            'destination' => '153',
            'weight' => 1000,
            'courier' => 'jne'
        ]
    ]);

    echo 'Status: ' . $response->getStatusCode() . PHP_EOL;
    $data = json_decode($response->getBody(), true);
    echo 'API Status: ' . $data['rajaongkir']['status']['description'] . PHP_EOL;
    
    if (!empty($data['rajaongkir']['results'])) {
        echo 'Results found: ' . count($data['rajaongkir']['results']) . PHP_EOL;
        $cost = $data['rajaongkir']['results'][0]['costs'][0]['cost'][0]['value'];
        echo 'Sample cost: Rp ' . number_format($cost) . PHP_EOL;
    } else {
        echo 'No results found' . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>
