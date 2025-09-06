<?php

$url = 'http://localhost/viviashop-app-main/viviashop-app-main/public/admin/variants/create';

$data = [
    'product_id' => 3,
    'name' => 'HTTP Test Variant - ' . date('H:i:s'),
    'sku' => 'HTTP-TEST-' . time(),
    'price' => 200000,
    'stock' => 20,
    'weight' => 300,
    'attributes' => [
        [
            'attribute_name' => 'Material',
            'attribute_value' => 'Cotton'
        ],
        [
            'attribute_name' => 'Pattern',
            'attribute_value' => 'Striped'
        ]
    ]
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query($data)
    ]
]);

echo "Testing HTTP request to admin variant creation...\n";
echo "URL: $url\n";
echo "Data: " . json_encode($data) . "\n\n";

$result = file_get_contents($url, false, $context);

if ($result === false) {
    echo "Request failed\n";
    if (isset($http_response_header)) {
        echo "Response headers:\n";
        foreach ($http_response_header as $header) {
            echo "  $header\n";
        }
    }
} else {
    echo "Response: $result\n";
}

echo "\nTest completed!\n";
