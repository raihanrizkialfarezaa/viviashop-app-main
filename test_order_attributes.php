<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Models\Product;
use App\Models\Attribute;

echo "=== Testing Order Attributes System ===\n\n";

echo "1. Checking Dummy Product2 (ID: 117)...\n";
$product = Product::find(117);
if ($product) {
    echo "Product found: {$product->name} (Type: {$product->type})\n";
    
    if ($product->type === 'configurable') {
        echo "This is a configurable product. Checking attributes...\n";
        
        $configurableAttributes = $product->configurableAttributes();
        echo "Number of configurable attributes: " . $configurableAttributes->count() . "\n";
        
        foreach ($configurableAttributes as $attribute) {
            echo "\nAttribute: {$attribute->name} (Code: {$attribute->code})\n";
            echo "Variants:\n";
            
            foreach ($attribute->attribute_variants as $variant) {
                echo "  - {$variant->name} (ID: {$variant->id})\n";
                echo "    Options available: {$variant->attribute_options->count()}\n";
            }
        }
    } else {
        echo "This is a simple product - no attributes needed.\n";
    }
} else {
    echo "Product not found!\n";
}

echo "\n2. Testing attribute endpoint...\n";
if ($product && $product->type === 'configurable') {
    $url = "http://127.0.0.1:8000/admin/products/{$product->id}/attributes";
    echo "Endpoint: {$url}\n";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Accept: application/json',
                'Content-Type: application/json'
            ]
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response) {
        $data = json_decode($response, true);
        echo "Response received:\n";
        echo "Number of attributes: " . count($data['attributes'] ?? []) . "\n";
    } else {
        echo "Failed to fetch attributes endpoint\n";
    }
}

echo "\n=== Test completed ===\n";
