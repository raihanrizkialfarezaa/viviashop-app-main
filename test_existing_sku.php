<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Services\ProductVariantService;

try {
    echo "=== TESTING WITH EXISTING SKU 9787080 ===\n";
    
    $service = new ProductVariantService();
    $controller = new ProductVariantController($service);
    
    $requestData = [
        'product_id' => 133,
        'name' => 'astaghfirullah',
        'sku' => '9787080',
        'price' => 4,
        'stock' => 10,
        'weight' => 10,
        'attributes' => [
            [
                'attribute_name' => 'pink',
                'attribute_value' => 'blue'
            ]
        ]
    ];
    
    echo "Testing with SKU that already exists: 9787080\n\n";
    
    $request = Request::create('/admin/variants/create', 'POST', $requestData);
    
    $response = $controller->store($request);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content: " . $response->getContent() . "\n";
    
    if ($response->getStatusCode() == 200) {
        $responseData = json_decode($response->getContent(), true);
        if (isset($responseData['variant'])) {
            $variant = $responseData['variant'];
            echo "\nVariant created with auto-generated SKU:\n";
            echo "Original SKU requested: 9787080\n";
            echo "Auto-generated SKU: {$variant['sku']}\n";
            echo "Name: {$variant['name']}\n";
            echo "Price: {$variant['price']}\n";
        }
    }
    
    echo "\n=== TEST COMPLETED ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
