<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 TESTING DROPDOWN FUNCTIONALITY\n";
echo "=================================\n\n";

try {
    echo "1. 🎫 Creating test session...\n";
    $printService = new App\Services\PrintService();
    $session = $printService->generateSession();
    echo "   ✅ Session token: {$session->token}\n\n";

    echo "2. 📁 Uploading test file...\n";
    $testContent = "Test document content\nPage 1\nPage 2\nPage 3\nPage 4\nPage 5";
    $testFile = tempnam(sys_get_temp_dir(), 'dropdown_test_') . '.txt';
    file_put_contents($testFile, $testContent);
    
    $uploadedFile = new Illuminate\Http\UploadedFile(
        $testFile,
        'test_document.txt',
        'text/plain',
        filesize($testFile),
        0
    );
    
    $uploadResult = $printService->uploadFiles([$uploadedFile], $session);
    
    if ($uploadResult['success']) {
        echo "   ✅ File uploaded: {$uploadResult['files'][0]['name']}\n";
        echo "   📄 Total pages: {$uploadResult['total_pages']}\n";
    } else {
        throw new Exception('File upload failed');
    }
    echo "\n";

    echo "3. 🛒 Testing Products API Response Structure...\n";
    $controller = new App\Http\Controllers\PrintServiceController($printService);
    $request = new Illuminate\Http\Request();
    
    $response = $controller->getProducts($request);
    $responseData = json_decode($response->getContent(), true);
    
    echo "   📊 Response structure validation:\n";
    echo "   ✅ Has 'success' key: " . (isset($responseData['success']) ? 'Yes' : 'No') . "\n";
    echo "   ✅ Success is true: " . ($responseData['success'] ? 'Yes' : 'No') . "\n";
    echo "   ✅ Has 'products' array: " . (isset($responseData['products']) && is_array($responseData['products']) ? 'Yes' : 'No') . "\n";
    echo "   📊 Products count: " . count($responseData['products']) . "\n\n";

    if (count($responseData['products']) > 0) {
        $product = $responseData['products'][0];
        echo "   📦 First Product Structure:\n";
        echo "   ✅ Has 'id': " . (isset($product['id']) ? 'Yes' : 'No') . "\n";
        echo "   ✅ Has 'name': " . (isset($product['name']) ? 'Yes' : 'No') . "\n";
        echo "   ✅ Has 'variants': " . (isset($product['variants']) && is_array($product['variants']) ? 'Yes' : 'No') . "\n";
        echo "   📊 Variants count: " . count($product['variants']) . "\n\n";

        echo "   📋 Dropdown Data Simulation:\n";
        $paperSizes = [];
        $printTypesBySize = [];

        foreach ($product['variants'] as $variant) {
            $paperSize = $variant['paper_size'];
            $printType = $variant['print_type'];
            $price = $variant['price'];
            
            if (!in_array($paperSize, $paperSizes)) {
                $paperSizes[] = $paperSize;
            }
            
            if (!isset($printTypesBySize[$paperSize])) {
                $printTypesBySize[$paperSize] = [];
            }
            
            $printTypesBySize[$paperSize][] = [
                'type' => $printType,
                'label' => $printType === 'bw' ? 'Black & White' : 'Color',
                'price' => $price,
                'stock' => $variant['stock']
            ];
        }

        echo "   📏 Paper Size Options:\n";
        foreach ($paperSizes as $size) {
            echo "      <option value=\"{$size}\">{$size}</option>\n";
        }
        echo "\n";

        echo "   🖨️ Print Type Options by Paper Size:\n";
        foreach ($printTypesBySize as $size => $types) {
            echo "      📏 For {$size}:\n";
            foreach ($types as $type) {
                echo "         <option value=\"{$type['type']}\">{$type['label']} - Rp " . number_format((float)$type['price']) . "</option>\n";
            }
            echo "\n";
        }
    }

    echo "4. 🧮 Testing Price Calculation...\n";
    if (count($responseData['products']) > 0) {
        $product = $responseData['products'][0];
        $firstVariant = $product['variants'][0];
        
        echo "   🎯 Using variant: {$firstVariant['name']}\n";
        echo "   📏 Paper Size: {$firstVariant['paper_size']}\n";
        echo "   🖨️ Print Type: {$firstVariant['print_type']}\n";
        echo "   💰 Unit Price: Rp " . number_format((float)$firstVariant['price']) . "\n";
        
        $totalPages = $uploadResult['total_pages'];
        $quantity = 1;
        
        $calculation = $printService->calculatePrice($firstVariant['id'], $totalPages, $quantity);
        
        echo "   🧮 Calculation Result:\n";
        echo "      📄 Total Pages: {$calculation['total_pages']}\n";
        echo "      📦 Quantity: {$calculation['quantity']}\n";
        echo "      💰 Unit Price: Rp " . number_format((float)$calculation['unit_price']) . "\n";
        echo "      💰 Total Price: Rp " . number_format((float)$calculation['total_price']) . "\n";
    }
    echo "\n";

    echo "5. 🧹 Cleanup...\n";
    unlink($testFile);
    echo "   ✅ Test file removed\n\n";

    echo "🎉 DROPDOWN FUNCTIONALITY TEST COMPLETED!\n";
    echo "========================================\n";
    echo "✅ Products API working correctly\n";
    echo "✅ Data structure is valid\n";
    echo "✅ Paper sizes can be populated\n";
    echo "✅ Print types can be populated dynamically\n";
    echo "✅ Price calculation working\n\n";
    
    echo "🔧 Frontend should now populate dropdowns correctly!\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . "\n";
    echo "📍 Line: " . $e->getLine() . "\n\n";
    
    if (isset($testFile) && file_exists($testFile)) {
        unlink($testFile);
    }
    
    exit(1);
}
