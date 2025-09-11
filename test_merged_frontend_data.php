<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\PrintService;
use App\Http\Controllers\PrintServiceController;
use Illuminate\Http\Request;

echo "=== TESTING MERGED FRONTEND STOCK DATA ===\n\n";

$printService = new PrintService();

echo "1. TESTING API ENDPOINT WITH MERGED DATA...\n";
$controller = new PrintServiceController($printService);
$request = new Request();

$response = $controller->getProducts($request);
$responseData = json_decode($response->getContent(), true);

if ($responseData['success']) {
    echo "API Response shows " . count($responseData['products']) . " products\n\n";
    
    $allVariants = [];
    foreach ($responseData['products'] as $product) {
        echo "Product: {$product['name']}\n";
        foreach ($product['variants'] as $variant) {
            echo "  - ID: {$variant['id']} | {$variant['paper_size']} {$variant['print_type']} | Stock: {$variant['stock']}\n";
            $allVariants[] = $variant;
        }
        echo "\n";
    }
    
    echo "2. SIMULATING MERGED FRONTEND DATA STRUCTURE...\n";
    
    $mergedData = array_reduce($responseData['products'], function($merged, $product) {
        if (!isset($merged['id'])) {
            $merged = [
                'id' => $product['id'],
                'name' => $product['name'],
                'variants' => $product['variants']
            ];
        } else {
            $merged['variants'] = array_merge($merged['variants'], $product['variants']);
        }
        return $merged;
    }, []);
    
    echo "Merged Product Data:\n";
    echo "Name: {$mergedData['name']}\n";
    echo "Total Variants: " . count($mergedData['variants']) . "\n\n";
    
    echo "3. TESTING DROPDOWN POPULATION WITH MERGED DATA...\n";
    
    $paperSizes = array_unique(array_column($mergedData['variants'], 'paper_size'));
    echo "Available Paper Sizes: " . implode(', ', $paperSizes) . "\n\n";
    
    foreach ($paperSizes as $paperSize) {
        echo "For Paper Size '{$paperSize}':\n";
        
        $typesForSize = array_filter($mergedData['variants'], function($variant) use ($paperSize) {
            return $variant['paper_size'] === $paperSize;
        });
        
        foreach ($typesForSize as $variant) {
            $stockStatus = '';
            if ($variant['stock'] <= 0) {
                $stockStatus = ' (Out of Stock)';
            } elseif ($variant['stock'] <= 1000) {
                $stockStatus = " (Stock: {$variant['stock']})";
            } else {
                $stockStatus = " (Stock: {$variant['stock']})";
            }
            
            $label = $variant['print_type'] === 'bw' ? 'Black & White' : 'Color';
            $disabled = $variant['stock'] <= 0 ? ' [DISABLED]' : '';
            
            echo "  - {$label} - Rp " . number_format($variant['price']) . "{$stockStatus}{$disabled}\n";
        }
        echo "\n";
    }
    
    echo "4. CHECKING FOR DUPLICATE COMBINATIONS...\n";
    
    $combinations = [];
    foreach ($mergedData['variants'] as $variant) {
        $combo = $variant['paper_size'] . '_' . $variant['print_type'];
        if (isset($combinations[$combo])) {
            echo "❌ DUPLICATE FOUND: {$variant['paper_size']} {$variant['print_type']}\n";
            echo "   First: ID {$combinations[$combo]['id']} - Stock {$combinations[$combo]['stock']}\n";
            echo "   Second: ID {$variant['id']} - Stock {$variant['stock']}\n";
        } else {
            $combinations[$combo] = $variant;
        }
    }
    
    if (count($combinations) == count($mergedData['variants'])) {
        echo "✅ NO DUPLICATES FOUND\n";
    }
    
    echo "\n5. FINAL STOCK SUMMARY...\n";
    
    foreach ($combinations as $combo => $variant) {
        $parts = explode('_', $combo);
        $size = $parts[0];
        $type = $parts[1];
        $label = $type === 'bw' ? 'Black & White' : 'Color';
        
        echo "- {$size} {$label}: {$variant['stock']} sheets (ID: {$variant['id']})\n";
    }
    
} else {
    echo "❌ API ERROR: " . ($responseData['error'] ?? 'Unknown error') . "\n";
}

echo "\n=== MERGED DATA STRUCTURE READY FOR FRONTEND ===\n";
