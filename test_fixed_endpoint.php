<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔄 TESTING FIXED PRODUCTS ENDPOINT\n";
echo "===================================\n\n";

try {
    echo "1. 🌐 Testing Products Route After Fix...\n";
    
    $request = \Illuminate\Http\Request::create('/print-service/products', 'GET');
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('Content-Type', 'application/json');
    
    $app = app();
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    
    $response = $kernel->handle($request);
    
    echo "   Status: " . $response->getStatusCode() . "\n";
    $responseContent = $response->getContent();
    echo "   Content Type: " . $response->headers->get('content-type') . "\n";
    echo "   Content Length: " . strlen($responseContent) . " bytes\n";
    
    if (str_contains($responseContent, 'DOCTYPE')) {
        echo "   ❌ STILL RETURNS HTML\n";
        echo "   First 200 chars: " . substr($responseContent, 0, 200) . "\n";
    } else {
        echo "   ✅ Returns JSON response!\n";
        
        $data = json_decode($responseContent, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "   ✅ Valid JSON structure\n";
            echo "   📊 Products: " . count($data['products']) . "\n";
            
            if (count($data['products']) > 0) {
                $product = $data['products'][0];
                echo "   📦 Product: {$product['name']}\n";
                echo "   📋 Variants: " . count($product['variants']) . "\n";
                
                echo "   📏 Available Paper Sizes:\n";
                $paperSizes = array_unique(array_column($product['variants'], 'paper_size'));
                foreach ($paperSizes as $size) {
                    echo "      • {$size}\n";
                }
                
                echo "   🖨️ Available Print Types:\n";
                $printTypes = array_unique(array_column($product['variants'], 'print_type'));
                foreach ($printTypes as $type) {
                    $label = $type === 'bw' ? 'Black & White' : 'Color';
                    echo "      • {$label} ({$type})\n";
                }
            }
        } else {
            echo "   ❌ Invalid JSON structure\n";
        }
    }
    echo "\n";

    echo "2. 🎯 Testing Frontend Data Structure...\n";
    if (isset($data) && $data['success']) {
        $product = $data['products'][0];
        
        echo "   JavaScript simulation:\n";
        echo "   productData = " . json_encode($product, JSON_PRETTY_PRINT) . "\n\n";
        
        echo "   Paper Size Dropdown Population:\n";
        $paperSizes = array_unique(array_column($product['variants'], 'paper_size'));
        foreach ($paperSizes as $size) {
            echo "      <option value=\"{$size}\">{$size}</option>\n";
        }
        echo "\n";
        
        echo "   Print Type Options (A4 example):\n";
        $a4Variants = array_filter($product['variants'], function($v) {
            return $v['paper_size'] === 'A4';
        });
        foreach ($a4Variants as $variant) {
            $label = $variant['print_type'] === 'bw' ? 'Black & White' : 'Color';
            $price = number_format((float)$variant['price']);
            echo "      <option value=\"{$variant['print_type']}\">{$label} - Rp {$price}</option>\n";
        }
    }
    echo "\n";

    echo "🎉 PRODUCTS ENDPOINT TESTING COMPLETED!\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . "\n";
    echo "📍 Line: " . $e->getLine() . "\n\n";
    exit(1);
}
