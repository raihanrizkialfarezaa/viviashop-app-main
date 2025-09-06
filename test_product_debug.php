<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "=== PRODUCT DEBUG TEST ===\n\n";

// Test 1: Check if problematic product exists
echo "1. Testing product lookup by slug 'baju-pria-lengan-panjang-2':\n";
$product = Product::where('slug', 'baju-pria-lengan-panjang-2')->first();
if ($product) {
    echo "   ✅ Product found: {$product->name}\n";
    echo "   - Type: {$product->type}\n";
    echo "   - ID: {$product->id}\n";
    echo "   - Status: {$product->status}\n";
    echo "   - Parent ID: " . ($product->parent_id ?? 'null') . "\n";
    
    // Test productImages relationship
    echo "   - Testing productImages relationship:\n";
    try {
        $images = $product->productImages;
        echo "     ✅ productImages loaded successfully, count: " . $images->count() . "\n";
        
        if ($images->count() > 0) {
            echo "     - First image path: " . $images->first()->path . "\n";
        } else {
            echo "     - No images found for this product\n";
        }
    } catch (Exception $e) {
        echo "     ❌ Error loading productImages: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ Product NOT found with slug: baju-pria-lengan-panjang-2\n";
}

echo "\n";

// Test 2: Check all configurable products and their slugs
echo "2. All configurable products:\n";
$configurableProducts = Product::where('type', 'configurable')->get(['id', 'name', 'slug', 'status']);
foreach ($configurableProducts as $prod) {
    echo "   - ID: {$prod->id}, Name: {$prod->name}, Slug: {$prod->slug}, Status: {$prod->status}\n";
}

echo "\n";

// Test 3: Check if any products have null productImages issue
echo "3. Testing productImages relationship for all configurable products:\n";
$configurableProducts = Product::where('type', 'configurable')->get();
foreach ($configurableProducts as $prod) {
    try {
        $images = $prod->productImages;
        echo "   ✅ {$prod->slug}: productImages OK, count: " . $images->count() . "\n";
    } catch (Exception $e) {
        echo "   ❌ {$prod->slug}: productImages ERROR - " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 4: Check Laravel route model binding behavior
echo "4. Testing Laravel route model binding simulation:\n";
try {
    // Simulate what Laravel does for route model binding
    $product = Product::where('slug', 'baju-pria-lengan-panjang-2')->firstOrFail();
    echo "   ✅ Route model binding would work for 'baju-pria-lengan-panjang-2'\n";
    echo "   - Product: {$product->name}\n";
} catch (Exception $e) {
    echo "   ❌ Route model binding would fail: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 5: Check for any duplicate slugs
echo "5. Checking for duplicate slugs:\n";
$slugCounts = Product::selectRaw('slug, COUNT(*) as count')
    ->groupBy('slug')
    ->having('count', '>', 1)
    ->get();

if ($slugCounts->count() > 0) {
    echo "   ❌ Found duplicate slugs:\n";
    foreach ($slugCounts as $slugCount) {
        echo "     - Slug '{$slugCount->slug}' appears {$slugCount->count} times\n";
    }
} else {
    echo "   ✅ No duplicate slugs found\n";
}

echo "\n=== END DEBUG TEST ===\n";
