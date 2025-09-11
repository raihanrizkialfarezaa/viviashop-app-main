<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 DEBUGGING PRODUCTS ENDPOINT\n";
echo "==============================\n\n";

try {
    echo "1. 🏪 Testing Print Products Service...\n";
    $printService = new App\Services\PrintService();
    $products = $printService->getPrintProducts();
    
    echo "   📊 Total products found: " . $products->count() . "\n";
    
    if ($products->count() > 0) {
        foreach ($products as $product) {
            echo "   📦 Product: {$product->name} (ID: {$product->id})\n";
            echo "      🏷️ Is Print Service: " . ($product->is_print_service ? 'Yes' : 'No') . "\n";
            
            $variants = $product->activeVariants;
            echo "      📋 Active Variants: " . $variants->count() . "\n";
            
            foreach ($variants as $variant) {
                echo "         🔸 {$variant->name}\n";
                echo "            📏 Paper Size: {$variant->paper_size}\n";
                echo "            🖨️ Print Type: {$variant->print_type}\n";
                echo "            💰 Price: Rp " . number_format((float)$variant->price) . "\n";
                echo "            📦 Stock: {$variant->stock}\n";
                echo "            ✅ Active: " . ($variant->is_active ? 'Yes' : 'No') . "\n\n";
            }
        }
    } else {
        echo "   ❌ No print products found!\n";
    }
    
    echo "\n2. 🌐 Testing Products API Endpoint...\n";
    
    $controller = new App\Http\Controllers\PrintServiceController(new App\Services\PrintService());
    $request = new Illuminate\Http\Request();
    
    $response = $controller->getProducts($request);
    $responseData = json_decode($response->getContent(), true);
    
    echo "   📡 API Response Status: " . $response->getStatusCode() . "\n";
    echo "   ✅ Success: " . ($responseData['success'] ? 'Yes' : 'No') . "\n";
    
    if (isset($responseData['products'])) {
        echo "   📊 Products in response: " . count($responseData['products']) . "\n";
        
        foreach ($responseData['products'] as $productData) {
            echo "   📦 Product: {$productData['name']} (ID: {$productData['id']})\n";
            echo "      📋 Variants: " . count($productData['variants']) . "\n";
            
            $paperSizes = [];
            $printTypes = [];
            
            foreach ($productData['variants'] as $variantData) {
                echo "         🔸 {$variantData['name']}\n";
                echo "            📏 Paper Size: {$variantData['paper_size']}\n";
                echo "            🖨️ Print Type: {$variantData['print_type']}\n";
                echo "            💰 Price: Rp " . number_format((float)$variantData['price']) . "\n";
                echo "            📦 Stock: {$variantData['stock']}\n\n";
                
                $paperSizes[] = $variantData['paper_size'];
                $printTypes[] = $variantData['print_type'];
            }
            
            echo "   🎯 Unique Paper Sizes: " . implode(', ', array_unique($paperSizes)) . "\n";
            echo "   🎯 Unique Print Types: " . implode(', ', array_unique($printTypes)) . "\n";
        }
    } else {
        echo "   ❌ No products data in API response!\n";
    }
    
    echo "\n3. 🔍 Raw API Response:\n";
    echo json_encode($responseData, JSON_PRETTY_PRINT);
    echo "\n\n";

    echo "🎉 PRODUCTS DEBUGGING COMPLETED!\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . "\n";
    echo "📍 Line: " . $e->getLine() . "\n\n";
    exit(1);
}
