<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ProductController;

Route::get('/test-product-attributes/{id}', function($id) {
    $productController = new ProductController();
    $response = $productController->getAttributes($id);
    
    if ($response instanceof \Illuminate\Http\JsonResponse) {
        $data = $response->getData(true);
        echo "=== Product Attributes Test ===\n";
        echo "Product ID: $id\n";
        echo "Response Status: " . $response->getStatusCode() . "\n";
        echo "Attributes count: " . count($data['attributes'] ?? []) . "\n\n";
        
        foreach ($data['attributes'] ?? [] as $attr) {
            echo "Attribute: {$attr['name']} (Code: {$attr['code']})\n";
            echo "Variants:\n";
            foreach ($attr['attribute_variants'] ?? [] as $variant) {
                echo "  - {$variant['name']} (ID: {$variant['id']})\n";
            }
            echo "\n";
        }
    }
    
    return 'Test completed - check terminal output';
});
