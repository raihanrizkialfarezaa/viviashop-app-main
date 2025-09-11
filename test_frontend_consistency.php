<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\PrintService;
use App\Http\Controllers\PrintServiceController;
use Illuminate\Http\Request;

echo "=== TESTING FRONTEND STOCK DATA CONSISTENCY ===\n\n";

$printService = new PrintService();

echo "1. CHECKING PRINT SERVICE PRODUCTS...\n";
$products = $printService->getPrintProducts();

echo "Products from PrintService:\n";
foreach ($products as $product) {
    echo "Product: {$product->name}\n";
    foreach ($product->activeVariants as $variant) {
        echo "  - ID: {$variant->id} | {$variant->paper_size} {$variant->print_type} | Stock: {$variant->stock}\n";
    }
    echo "\n";
}

echo "2. TESTING API ENDPOINT getProducts()...\n";
$controller = new PrintServiceController($printService);
$request = new Request();

$response = $controller->getProducts($request);
$responseData = json_decode($response->getContent(), true);

if ($responseData['success']) {
    echo "API Response Products:\n";
    foreach ($responseData['products'] as $product) {
        echo "Product: {$product['name']}\n";
        foreach ($product['variants'] as $variant) {
            echo "  - ID: {$variant['id']} | {$variant['paper_size']} {$variant['print_type']} | Stock: {$variant['stock']}\n";
        }
        echo "\n";
    }
} else {
    echo "❌ API ERROR: " . ($responseData['error'] ?? 'Unknown error') . "\n";
}

echo "3. CHECKING STOCK CONSISTENCY...\n";
$directVariants = [];
foreach ($products as $product) {
    foreach ($product->activeVariants as $variant) {
        $directVariants[$variant->id] = $variant->stock;
    }
}

$apiVariants = [];
foreach ($responseData['products'] as $product) {
    foreach ($product['variants'] as $variant) {
        $apiVariants[$variant['id']] = $variant['stock'];
    }
}

echo "Stock Comparison:\n";
$inconsistencies = 0;
foreach ($directVariants as $variantId => $directStock) {
    $apiStock = $apiVariants[$variantId] ?? 'NOT FOUND';
    if ($directStock != $apiStock) {
        echo "❌ INCONSISTENT - Variant ID {$variantId}: Direct={$directStock}, API={$apiStock}\n";
        $inconsistencies++;
    } else {
        echo "✅ CONSISTENT - Variant ID {$variantId}: Stock={$directStock}\n";
    }
}

if ($inconsistencies == 0) {
    echo "\n✅ ALL STOCK DATA CONSISTENT BETWEEN DIRECT AND API ACCESS\n";
} else {
    echo "\n❌ FOUND {$inconsistencies} INCONSISTENCIES\n";
}

echo "\n4. TESTING FRONTEND PRINT TYPE OPTIONS...\n";

use App\Models\ProductVariant;

$printTypes = ProductVariant::whereHas('product', function($query) {
    $query->where('is_print_service', true);
})->select('print_type')
  ->distinct()
  ->get()
  ->pluck('print_type');

echo "Available Print Types:\n";
foreach ($printTypes as $type) {
    echo "- {$type}\n";
}

echo "\n5. CHECKING FRONTEND VIEW DATA...\n";
echo "View products structure should match API:\n";
foreach ($products as $product) {
    echo "Product ID {$product->id}: {$product->name}\n";
    echo "  Active Variants: " . $product->activeVariants->count() . "\n";
    foreach ($product->activeVariants as $variant) {
        echo "    - {$variant->paper_size} {$variant->print_type}: {$variant->stock} stock\n";
    }
    echo "\n";
}
