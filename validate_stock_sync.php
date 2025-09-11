<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\PrintService;
use App\Services\StockManagementService;
use App\Models\ProductVariant;

echo "=== FINAL STOCK CONSISTENCY VALIDATION ===\n\n";

$printService = new PrintService();
$stockService = new StockManagementService();

echo "1. TESTING FRONTEND DEDUPLICATED DATA...\n";

$products = $printService->getPrintProducts();
$responseData = [
    'success' => true,
    'products' => $products->map(function($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'variants' => $product->activeVariants->map(function($variant) {
                return [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'price' => $variant->price,
                    'print_type' => $variant->print_type,
                    'paper_size' => $variant->paper_size,
                    'stock' => $variant->stock
                ];
            })
        ];
    })
];

$allVariants = [];
$seenCombinations = [];

foreach ($responseData['products'] as $product) {
    foreach ($product['variants'] as $variant) {
        $combo = $variant['paper_size'] . '_' . $variant['print_type'];
        if (!in_array($combo, $seenCombinations)) {
            $allVariants[] = $variant;
            $seenCombinations[] = $combo;
        } else {
            $existingIndex = array_search($combo, array_map(function($v) {
                return $v['paper_size'] . '_' . $v['print_type'];
            }, $allVariants));
            
            if ($existingIndex !== false) {
                $allVariants[$existingIndex]['stock'] = max(
                    $allVariants[$existingIndex]['stock'], 
                    $variant['stock']
                );
                echo "Merged duplicate: {$variant['paper_size']} {$variant['print_type']} - Using highest stock: {$allVariants[$existingIndex]['stock']}\n";
            }
        }
    }
}

$deduplicatedData = [
    'id' => $responseData['products'][0]['id'],
    'name' => $responseData['products'][0]['name'],
    'variants' => $allVariants
];

echo "\nDeduplicated Frontend Data:\n";
echo "Product: {$deduplicatedData['name']}\n";
echo "Unique Variants: " . count($deduplicatedData['variants']) . "\n\n";

foreach ($deduplicatedData['variants'] as $variant) {
    echo "- {$variant['paper_size']} {$variant['print_type']}: {$variant['stock']} stock (ID: {$variant['id']})\n";
}

echo "\n2. COMPARING WITH ADMIN STOCK DATA...\n";

$adminVariants = $stockService->getVariantsByStock('asc');

echo "Admin Stock Data:\n";
foreach ($adminVariants as $adminVariant) {
    echo "- {$adminVariant->paper_size} {$adminVariant->print_type}: {$adminVariant->stock} stock (ID: {$adminVariant->id})\n";
}

echo "\n3. STOCK CONSISTENCY CHECK...\n";

$consistent = true;
foreach ($deduplicatedData['variants'] as $frontendVariant) {
    $adminVariant = $adminVariants->where('id', $frontendVariant['id'])->first();
    
    if (!$adminVariant) {
        echo "❌ Frontend variant ID {$frontendVariant['id']} not found in admin\n";
        $consistent = false;
    } elseif ($frontendVariant['stock'] != $adminVariant->stock) {
        echo "❌ Stock mismatch: {$frontendVariant['paper_size']} {$frontendVariant['print_type']} - Frontend: {$frontendVariant['stock']}, Admin: {$adminVariant->stock}\n";
        $consistent = false;
    } else {
        echo "✅ Consistent: {$frontendVariant['paper_size']} {$frontendVariant['print_type']} - Stock: {$frontendVariant['stock']}\n";
    }
}

echo "\n4. DROPDOWN SIMULATION TEST...\n";

$paperSizes = array_unique(array_column($deduplicatedData['variants'], 'paper_size'));
echo "Paper sizes: " . implode(', ', $paperSizes) . "\n\n";

foreach ($paperSizes as $paperSize) {
    echo "For Paper Size '{$paperSize}':\n";
    
    $typesForSize = array_filter($deduplicatedData['variants'], function($variant) use ($paperSize) {
        return $variant['paper_size'] === $paperSize;
    });
    
    foreach ($typesForSize as $variant) {
        $stockStatus = '';
        if ($variant['stock'] <= 0) {
            $stockStatus = ' (Out of Stock)';
        } elseif ($variant['stock'] <= 1000) {
            $stockStatus = " (Low Stock: {$variant['stock']})";
        } else {
            $stockStatus = " (Stock: {$variant['stock']})";
        }
        
        $label = $variant['print_type'] === 'bw' ? 'Black & White' : 'Color';
        $disabled = $variant['stock'] <= 0 ? ' [DISABLED]' : '';
        
        echo "  - {$label} - Rp " . number_format($variant['price']) . "{$stockStatus}{$disabled}\n";
    }
    echo "\n";
}

if ($consistent) {
    echo "✅ ALL STOCK DATA IS NOW CONSISTENT BETWEEN FRONTEND AND ADMIN\n";
    echo "✅ FRONTEND WILL SHOW CORRECT STOCK INFORMATION\n";
    echo "✅ NO DUPLICATE ENTRIES IN DROPDOWN\n";
} else {
    echo "❌ INCONSISTENCIES STILL EXIST\n";
}

echo "\n=== FRONTEND STOCK SYNC COMPLETE ===\n";
