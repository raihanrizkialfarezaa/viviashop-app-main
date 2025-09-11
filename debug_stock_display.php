<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\PrintService;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

echo "=== TESTING FRONTEND STOCK DISPLAY CONSISTENCY ===\n\n";

$printService = new PrintService();

echo "1. SIMULATING FRONTEND API CALL...\n";
$products = $printService->getPrintProducts();

if ($products->count() == 0) {
    echo "❌ NO PRINT SERVICE PRODUCTS FOUND\n";
    exit;
}

$product = $products->first();
echo "Using Product: {$product->name} (ID: {$product->id})\n\n";

echo "2. CHECKING VARIANTS DATA STRUCTURE...\n";
foreach ($product->activeVariants as $variant) {
    echo "Variant ID {$variant->id}:\n";
    echo "  - Paper Size: {$variant->paper_size}\n";
    echo "  - Print Type: {$variant->print_type}\n";
    echo "  - Stock: {$variant->stock}\n";
    echo "  - Price: {$variant->price}\n";
    echo "  - Min Threshold: {$variant->min_stock_threshold}\n";
    
    $stockStatus = '';
    if ($variant->stock <= 0) {
        $stockStatus = 'OUT OF STOCK';
    } elseif ($variant->stock <= ($variant->min_stock_threshold ?? 100)) {
        $stockStatus = 'LOW STOCK';
    } else {
        $stockStatus = 'AVAILABLE';
    }
    echo "  - Status: {$stockStatus}\n\n";
}

echo "3. SIMULATING FRONTEND DROPDOWN POPULATION...\n";

$paperSizes = $product->activeVariants->pluck('paper_size')->unique()->values();
echo "Available Paper Sizes:\n";
foreach ($paperSizes as $size) {
    echo "- {$size}\n";
}
echo "\n";

foreach ($paperSizes as $paperSize) {
    echo "For Paper Size '{$paperSize}':\n";
    
    $typesForSize = $product->activeVariants
        ->where('paper_size', $paperSize)
        ->map(function($variant) {
            $stockStatus = '';
            if ($variant->stock <= 0) {
                $stockStatus = ' (Out of Stock)';
            } elseif ($variant->stock <= ($variant->min_stock_threshold ?? 100)) {
                $stockStatus = " (Low Stock: {$variant->stock})";
            } else {
                $stockStatus = " (Stock: {$variant->stock})";
            }
            
            $label = $variant->print_type === 'bw' ? 'Black & White' : 'Color';
            
            return [
                'value' => $variant->print_type,
                'label' => $label,
                'price' => $variant->price,
                'stock' => $variant->stock,
                'status' => $stockStatus,
                'disabled' => $variant->stock <= 0
            ];
        });
    
    foreach ($typesForSize as $type) {
        $disabledText = $type['disabled'] ? ' [DISABLED]' : '';
        echo "  - {$type['label']} - Rp " . number_format($type['price']) . "{$type['status']}{$disabledText}\n";
    }
    echo "\n";
}

echo "4. CHECKING ADMIN STOCK DATA CONSISTENCY...\n";

$adminVariants = ProductVariant::whereHas('product', function($query) {
    $query->where('is_print_service', true);
})->with('product')->get();

echo "Admin Stock Data:\n";
foreach ($adminVariants as $variant) {
    echo "- ID {$variant->id}: {$variant->paper_size} {$variant->print_type} = {$variant->stock} stock\n";
}
echo "\n";

echo "5. COMPARING FRONTEND VS ADMIN DATA...\n";
$frontendData = [];
foreach ($product->activeVariants as $variant) {
    $frontendData[$variant->id] = $variant->stock;
}

$adminData = [];
foreach ($adminVariants as $variant) {
    $adminData[$variant->id] = $variant->stock;
}

$inconsistencies = 0;
foreach ($frontendData as $variantId => $frontendStock) {
    $adminStock = $adminData[$variantId] ?? 'NOT FOUND';
    if ($frontendStock != $adminStock) {
        echo "❌ INCONSISTENT - Variant ID {$variantId}: Frontend={$frontendStock}, Admin={$adminStock}\n";
        $inconsistencies++;
    } else {
        echo "✅ CONSISTENT - Variant ID {$variantId}: Stock={$frontendStock}\n";
    }
}

if ($inconsistencies == 0) {
    echo "\n✅ ALL DATA CONSISTENT BETWEEN FRONTEND AND ADMIN\n";
} else {
    echo "\n❌ FOUND {$inconsistencies} INCONSISTENCIES - NEED TO INVESTIGATE\n";
}

echo "\n6. CHECKING DATABASE REAL-TIME VALUES...\n";
$realTimeVariants = DB::table('product_variants')
    ->join('products', 'product_variants.product_id', '=', 'products.id')
    ->where('products.is_print_service', true)
    ->select('product_variants.id', 'product_variants.paper_size', 'product_variants.print_type', 'product_variants.stock')
    ->get();

echo "Real-time Database Values:\n";
foreach ($realTimeVariants as $variant) {
    echo "- ID {$variant->id}: {$variant->paper_size} {$variant->print_type} = {$variant->stock} stock\n";
}
