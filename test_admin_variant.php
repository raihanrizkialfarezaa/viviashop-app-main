<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Set up environment
$app->make('Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables')->bootstrap($app);
$app->make('Illuminate\Foundation\Bootstrap\LoadConfiguration')->bootstrap($app);
$app->make('Illuminate\Foundation\Bootstrap\RegisterProviders')->bootstrap($app);
$app->make('Illuminate\Foundation\Bootstrap\BootProviders')->bootstrap($app);

use Illuminate\Http\Request;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Services\ProductVariantService;
use App\Models\Product;

try {
    echo "Testing Admin Variant Controller...\n";
    
    // Get first product
    $product = Product::first();
    if (!$product) {
        echo "No products found\n";
        exit;
    }
    
    echo "Testing with Product: {$product->id} - {$product->name}\n";
    
    // Create controller instance
    $service = new ProductVariantService();
    $controller = new ProductVariantController($service);
    
    // Create request data
    $requestData = [
        'product_id' => $product->id,
        'name' => 'Admin Test Variant - ' . date('H:i:s'),
        'sku' => 'ADMIN-TEST-' . time(),
        'price' => 150000,
        'stock' => 15,
        'weight' => 200,
        'attributes' => [
            [
                'attribute_name' => 'Color',
                'attribute_value' => 'Red'
            ],
            [
                'attribute_name' => 'Size',
                'attribute_value' => 'Large'
            ]
        ]
    ];
    
    // Create request
    $request = Request::create('/admin/variants/create', 'POST', $requestData);
    
    // Call controller
    $response = $controller->store($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content: " . $response->getContent() . "\n";
    
    echo "Test completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
