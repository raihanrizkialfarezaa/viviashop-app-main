<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\VariantAttribute;
use App\Models\ProductInventory;
use App\Models\ProductImage;

echo "=== CREATING MISSING PRODUCTS ===\n\n";

// Define missing products that are causing errors
$missingProducts = [
    [
        'slug' => 'baju-pria-lengan-panjang-2',
        'name' => 'Baju Pria Lengan Panjang 2',
        'description' => 'Baju pria lengan panjang berkualitas tinggi dengan berbagai variasi warna dan ukuran',
        'price' => 150000,
        'is_configurable' => true
    ],
    // Add more missing products if discovered
];

// Get or create a default category
$category = Category::first();
if (!$category) {
    echo "Creating default category...\n";
    $category = Category::create([
        'name' => 'Pakaian Pria',
        'slug' => 'pakaian-pria',
        'description' => 'Kategori untuk pakaian pria'
    ]);
}

foreach ($missingProducts as $productData) {
    echo "Checking product: {$productData['slug']}\n";
    
    // Check if product already exists
    $existingProduct = Product::where('slug', $productData['slug'])->first();
    
    if ($existingProduct) {
        echo "✅ Product '{$productData['slug']}' already exists\n\n";
        continue;
    }
    
    echo "🔄 Creating product: {$productData['slug']}\n";
    
    // Create the product
    $product = Product::create([
        'name' => $productData['name'],
        'slug' => $productData['slug'],
        'description' => $productData['description'],
        'price' => $productData['price'],
        'weight' => 500, // 500 grams default
        'status' => Product::ACTIVE, // 1 = active
        'type' => $productData['is_configurable'] ? Product::CONFIGURABLE : Product::SIMPLE,
        'sku' => strtoupper(str_replace('-', '', substr($productData['slug'], 0, 10)) . rand(1000, 9999)),
        'total_stock' => 100,
        'user_id' => 1,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    // Attach to category
    $product->categories()->attach($category->id);
    
    // Create default product image
    ProductImage::create([
        'product_id' => $product->id,
        'path' => 'product/images/default-product.jpg',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    // If configurable, create variants
    if ($productData['is_configurable'] && $product->type === Product::CONFIGURABLE) {
        echo "  📝 Creating variants for configurable product...\n";
        
        // Create size variants
        $sizes = ['S', 'M', 'L', 'XL'];
        $colors = ['Merah', 'Biru', 'Hitam', 'Putih'];
        
        $variantCounter = 1;
        foreach ($sizes as $size) {
            foreach ($colors as $color) {
                // Create variant
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => $productData['slug'] . '-' . strtolower($size) . '-' . strtolower($color),
                    'name' => $product->name . ' - ' . $size . ' ' . $color,
                    'price' => $productData['price'],
                    'weight' => 500,
                    'is_active' => true
                ]);
                
                // Create variant attributes
                VariantAttribute::create([
                    'variant_id' => $variant->id,
                    'attribute_name' => 'size',
                    'attribute_value' => $size
                ]);
                
                VariantAttribute::create([
                    'variant_id' => $variant->id,
                    'attribute_name' => 'color',
                    'attribute_value' => $color
                ]);
                
                // Create inventory
                ProductInventory::create([
                    'variant_id' => $variant->id,
                    'quantity' => rand(10, 100),
                    'reserved_quantity' => 0,
                    'location' => 'MAIN_WAREHOUSE'
                ]);
                
                $variantCounter++;
            }
        }
        
        echo "  ✅ Created " . count($sizes) * count($colors) . " variants\n";
    } else {
        // Create inventory for simple product
        ProductInventory::create([
            'product_id' => $product->id,
            'quantity' => rand(50, 200),
            'reserved_quantity' => 0,
            'location' => 'MAIN_WAREHOUSE'
        ]);
    }
    
    echo "✅ Successfully created product: {$productData['name']}\n\n";
}

echo "=== VERIFICATION ===\n";

// Verify all products now exist
foreach ($missingProducts as $productData) {
    $product = Product::where('slug', $productData['slug'])->first();
    if ($product) {
        echo "✅ {$productData['slug']} - EXISTS\n";
        echo "   - Product ID: {$product->id}\n";
        echo "   - Product Images: " . $product->productImages()->count() . "\n";
        if ($product->is_configurable) {
            echo "   - Variants: " . $product->productVariants()->count() . "\n";
        }
        echo "   - Inventory records: " . ProductInventory::where('product_id', $product->id)->orWhereHas('variant', function($q) use ($product) {
            $q->where('product_id', $product->id);
        })->count() . "\n";
    } else {
        echo "❌ {$productData['slug']} - STILL MISSING\n";
    }
    echo "\n";
}

echo "=== TESTING ROUTE MODEL BINDING ===\n";

foreach ($missingProducts as $productData) {
    echo "Testing route model binding for: {$productData['slug']}\n";
    $product = Product::where('slug', $productData['slug'])->first();
    
    if ($product) {
        try {
            // Simulate the same loading as ProductController
            $product->load(['productVariants.variantAttributes', 'productImages', 'categories']);
            echo "✅ Route model binding simulation: SUCCESS\n";
            echo "   - Product loaded with relationships\n";
            echo "   - productImages count: " . $product->productImages->count() . "\n";
        } catch (Exception $e) {
            echo "❌ Route model binding simulation: FAILED\n";
            echo "   - Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Product not found, route model binding would fail\n";
    }
    echo "\n";
}

echo "=== SUMMARY ===\n";
echo "Total configurable products in database: " . Product::where('is_configurable', true)->count() . "\n";
echo "Total product images in database: " . ProductImage::count() . "\n";
echo "Total product variants in database: " . ProductVariant::count() . "\n";
echo "Total inventory records in database: " . ProductInventory::count() . "\n";

echo "\n🎉 Product creation process completed!\n";
echo "📋 Next steps:\n";
echo "1. Test the product detail pages that were failing\n";
echo "2. Verify no more HTTP 500 errors occur\n";
echo "3. Check that productImages relationships work correctly\n";
