<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

// Initialize the application
$app->boot();

use App\Models\Product;
use App\Models\ProductVariant;

echo "=== SMART PRINT CONVERTER TOOL TEST ===\n\n";

// Test 1: Find regular products (non-smart print)
echo "1. MENCARI PRODUK BIASA (belum smart print)...\n";
$regularProducts = Product::where(function ($q) {
    $q->where('is_print_service', false)
      ->orWhere('is_smart_print_enabled', false)
      ->orWhereNull('is_print_service')
      ->orWhereNull('is_smart_print_enabled');
})->limit(5)->get();

echo "Ditemukan " . $regularProducts->count() . " produk biasa:\n";
foreach ($regularProducts as $product) {
    echo "- ID: {$product->id}, Name: {$product->name}\n";
    echo "  is_print_service: " . ($product->is_print_service ? 'true' : 'false') . "\n";
    echo "  is_smart_print_enabled: " . ($product->is_smart_print_enabled ? 'true' : 'false') . "\n";
    echo "  Variants count: " . $product->variants()->count() . "\n\n";
}

// Test 2: Simulate convert process for first product
if ($regularProducts->count() > 0) {
    $testProduct = $regularProducts->first();
    echo "2. TESTING CONVERT PROCESS untuk: {$testProduct->name}\n";
    
    // Check existing variants
    $existingVariants = $testProduct->variants()
        ->whereIn('name', ['BW', 'Color'])
        ->pluck('name')
        ->toArray();
    
    echo "Existing BW/Color variants: " . implode(', ', $existingVariants) . "\n";
    
    // Simulate what would happen in conversion
    echo "Would update product with:\n";
    echo "- is_print_service: true\n";
    echo "- is_smart_print_enabled: true\n";
    echo "- status: 1\n";
    
    if (!in_array('BW', $existingVariants)) {
        echo "- Would create BW variant\n";
    }
    
    if (!in_array('Color', $existingVariants)) {
        echo "- Would create Color variant\n";
    }
}

echo "\n3. CHECKING SMART PRINT PRODUCTS...\n";
$smartPrintProducts = Product::where('is_print_service', true)
                            ->where('is_smart_print_enabled', true)
                            ->limit(5)
                            ->get();

echo "Ditemukan " . $smartPrintProducts->count() . " smart print products:\n";
foreach ($smartPrintProducts as $product) {
    echo "- ID: {$product->id}, Name: {$product->name}\n";
    $bwVariant = $product->variants()->where('name', 'BW')->first();
    $colorVariant = $product->variants()->where('name', 'Color')->first();
    echo "  BW Variant: " . ($bwVariant ? "✅ {$bwVariant->sku}" : "❌ Missing") . "\n";
    echo "  Color Variant: " . ($colorVariant ? "✅ {$colorVariant->sku}" : "❌ Missing") . "\n\n";
}

echo "\n4. STATISTICS...\n";
$stats = [
    'total' => Product::count(),
    'smart_print' => Product::where('is_print_service', true)
                           ->where('is_smart_print_enabled', true)
                           ->count(),
    'regular' => Product::where(function ($q) {
        $q->where('is_print_service', false)
          ->orWhere('is_smart_print_enabled', false)
          ->orWhereNull('is_print_service')
          ->orWhereNull('is_smart_print_enabled');
    })->count(),
];

echo "Total Products: {$stats['total']}\n";
echo "Smart Print Products: {$stats['smart_print']}\n";
echo "Regular Products: {$stats['regular']}\n";

echo "\n5. TESTING SEARCH FUNCTIONALITY...\n";
$searchTerm = "kertas";
$searchResults = Product::where(function ($q) use ($searchTerm) {
    $q->where('name', 'like', "%{$searchTerm}%")
      ->orWhere('sku', 'like', "%{$searchTerm}%")
      ->orWhere('description', 'like', "%{$searchTerm}%");
})->limit(3)->get();

echo "Search results for '{$searchTerm}': {$searchResults->count()} found\n";
foreach ($searchResults as $product) {
    echo "- {$product->name} (Smart Print: " . 
         ($product->is_print_service && $product->is_smart_print_enabled ? 'Yes' : 'No') . ")\n";
}

echo "\n=== TEST COMPLETED ===\n";
echo "Smart Print Converter Tool siap digunakan!\n";
echo "Akses di: /admin/smart-print-converter\n";