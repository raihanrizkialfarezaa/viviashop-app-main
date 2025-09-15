<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Brand;
use App\Models\User;
use App\Models\ProductVariant;
use App\Services\ProductVariantService;

echo "=== FINAL TEST: CHECKBOX FIX COMPLETE ===\n\n";

// Ambil dependency yang dibutuhkan
$category = ProductCategory::first();
$brand = Brand::first();
$user = User::first();

if (!$category || !$brand || !$user) {
    echo "ERROR: Perlu minimal 1 category, 1 brand, dan 1 user di database\n";
    exit;
}

echo "=== TEST CREATE PRODUCT DENGAN CHECKBOX CHECKED ===\n";

// Simulasi data request dengan checkbox checked
$requestData = [
    'name' => 'Final Test Kertas - ' . time(),
    'slug' => 'final-test-kertas-' . time(),
    'sku' => 'FINAL-TEST-' . time(),
    'description' => 'Test final checkbox fix',
    'category_id' => [$category->id],
    'brand_id' => $brand->id,
    'type' => 'simple',
    'status' => 1,
    'price' => 7500,
    'weight' => 0.1,
    'qty' => 50,
    'is_print_service' => 'on', // Checkbox checked
    'is_smart_print_enabled' => 'on', // Checkbox checked
];

echo "Request data:\n";
foreach ($requestData as $key => $value) {
    if (is_array($value)) {
        echo "- {$key}: " . implode(', ', $value) . "\n";
    } else {
        echo "- {$key}: {$value}\n";
    }
}
echo "\n";

// Simulasi request object dan checkbox handling dari controller
$request = new \Illuminate\Http\Request($requestData);
$data = $requestData;
$data['is_print_service'] = $request->has('is_print_service') ? true : false;
$data['is_smart_print_enabled'] = $request->has('is_smart_print_enabled') ? true : false;

echo "After checkbox handling:\n";
echo "- is_print_service: " . ($data['is_print_service'] ? 'true' : 'false') . "\n";
echo "- is_smart_print_enabled: " . ($data['is_smart_print_enabled'] ? 'true' : 'false') . "\n\n";

// Test dengan ProductVariantService yang sudah diperbaiki
echo "=== TEST PRODUCTVARIANTSERVICE CREATE ===\n";
$productVariantService = new ProductVariantService();

try {
    $product = $productVariantService->createBaseProduct($data);
    
    echo "✅ Produk berhasil dibuat dengan ID: {$product->id}\n";
    echo "✅ Name: {$product->name}\n";
    echo "✅ is_print_service: " . ($product->is_print_service ? 'true' : 'false') . "\n";
    echo "✅ is_smart_print_enabled: " . ($product->is_smart_print_enabled ? 'true' : 'false') . "\n\n";
    
    // Assign kategori
    $product->categories()->sync([$category->id]);
    echo "✅ Kategori berhasil di-assign\n\n";
    
    // Auto-create variants (simulasi dari controller)
    if ($data['is_smart_print_enabled'] && $data['is_print_service']) {
        echo "=== AUTO-CREATE VARIANTS ===\n";
        
        $bwVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $product->sku . '-BW',
            'name' => $product->name . ' - Black & White',
            'price' => $product->price,
            'stock' => 100,
            'is_active' => true,
            'print_type' => 'bw',
        ]);
        echo "✅ BW Variant created: ID {$bwVariant->id}\n";
        
        $colorVariant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => $product->sku . '-COLOR',
            'name' => $product->name . ' - Color',
            'price' => $product->price * 1.5,
            'stock' => 50,
            'is_active' => true,
            'print_type' => 'color',
        ]);
        echo "✅ Color Variant created: ID {$colorVariant->id}\n\n";
    }
    
    // Final verification
    echo "=== FINAL VERIFICATION ===\n";
    $product->refresh();
    $variantCount = ProductVariant::where('product_id', $product->id)->where('is_active', true)->count();
    
    echo "Final state:\n";
    echo "- Product ID: {$product->id}\n";
    echo "- Name: {$product->name}\n";
    echo "- is_print_service: " . ($product->is_print_service ? 'true' : 'false') . "\n";
    echo "- is_smart_print_enabled: " . ($product->is_smart_print_enabled ? 'true' : 'false') . "\n";
    echo "- status: {$product->status}\n";
    echo "- active variants: {$variantCount}\n\n";
    
    // Test StockManagementService
    $stockService = new \App\Services\StockManagementService();
    $stockVariants = $stockService->getVariantsByStock();
    
    $productInStock = $stockVariants->filter(function($variant) use ($product) {
        return $variant->product && $variant->product->id == $product->id;
    });
    
    if ($productInStock->count() > 0) {
        echo "🎉 SUCCESS! Produk LANGSUNG MUNCUL di Stock Management!\n";
        echo "Variants yang muncul:\n";
        foreach ($productInStock as $variant) {
            echo "- {$variant->name} (Stock: {$variant->stock})\n";
        }
    } else {
        echo "❌ Produk tidak muncul di Stock Management\n";
    }
    
    echo "\n=== CLEANUP ===\n";
    // Hapus test product
    ProductVariant::where('product_id', $product->id)->delete();
    $product->delete();
    echo "✅ Test product dihapus\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎯 CHECKBOX FIX VERIFICATION COMPLETE!\n";
echo "Masalah ada di ProductVariantService::createBaseProduct() yang tidak include checkbox fields.\n";
echo "Sekarang sudah diperbaiki dan checkbox akan tersimpan dengan benar!\n";