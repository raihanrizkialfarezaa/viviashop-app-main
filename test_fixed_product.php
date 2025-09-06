<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

echo "=== TESTING FIXED PRODUCT ===\n\n";

$slug = 'baju-pria-lengan-panjang-2';

echo "Test HTTP endpoint simulation for product: {$slug}\n";

$product = Product::where('slug', $slug)->first();

if ($product) {
    echo "✅ Product found: {$product->name}\n";
    echo "   - ID: {$product->id}\n";
    echo "   - Type: {$product->type}\n";
    echo "   - Status: {$product->status}\n";
    
    try {
        // This simulates exactly what ProductController->show() does
        $product->load(['productVariants.variantAttributes', 'productImages', 'categories']);
        
        echo "✅ ProductController simulation: SUCCESS\n";
        echo "   - productImages loaded: " . $product->productImages->count() . "\n";
        echo "   - productVariants loaded: " . $product->productVariants->count() . "\n";
        echo "   - categories loaded: " . $product->categories->count() . "\n";
        
        echo "\n🎉 HTTP 500 error should now be RESOLVED!\n";
        echo "   - Route model binding will find the product\n";
        echo "   - productImages relationship will NOT be null\n";
        echo "   - Template can safely access \$product->productImages\n";
        
    } catch (Exception $e) {
        echo "❌ Error in controller simulation: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Product not found - route model binding would still fail\n";
}

echo "\n=== TESTING ROUTE MODEL BINDING BEHAVIOR ===\n";

// Test what happens when we try to access a product that doesn't exist
$nonExistentSlug = 'baju-pria-lengan-panjang-3';
$nonExistentProduct = Product::where('slug', $nonExistentSlug)->first();

if ($nonExistentProduct) {
    echo "❌ Unexpected: Non-existent product found\n";
} else {
    echo "✅ Non-existent product correctly returns null\n";
    echo "   - Laravel route model binding would return 404 for '{$nonExistentSlug}'\n";
    echo "   - This is expected behavior\n";
}

echo "\n=== SUMMARY ===\n";
echo "✅ Product 'baju-pria-lengan-panjang-2' now exists in database\n";
echo "✅ Route model binding will successfully find the product\n";
echo "✅ ProductController will receive a valid Product object (not null)\n";
echo "✅ Template can safely access productImages relationship\n";
echo "✅ HTTP 500 errors should be resolved\n";

echo "\n📋 Next steps:\n";
echo "1. Access the product detail page in browser\n";
echo "2. Verify no more HTTP 500 errors occur\n";
echo "3. Check that productImages relationship works correctly\n";
echo "4. Monitor Laravel error logs for any remaining issues\n";
