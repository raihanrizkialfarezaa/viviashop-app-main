<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Brand;
use App\Models\User;
use App\Models\ProductVariant;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST CHECKBOX FIX - SIMULASI CREATE PRODUCT ===\n\n";

// Ambil kategori, brand, dan user yang ada
$category = ProductCategory::first();
$brand = Brand::first();
$user = User::first();

if (!$category || !$brand || !$user) {
    echo "ERROR: Perlu minimal 1 category, 1 brand, dan 1 user di database\n";
    exit;
}

echo "Menggunakan:\n";
echo "- Category: {$category->name} (ID: {$category->id})\n";
echo "- Brand: {$brand->name} (ID: {$brand->id})\n";
echo "- User: {$user->name} (ID: {$user->id})\n\n";

// Simulasi request data dengan checkbox checked
$requestData = [
    'name' => 'Test Kertas Checkbox Fix',
    'slug' => 'test-kertas-checkbox-fix',
    'description' => 'Produk test untuk verifikasi fix checkbox',
    'category_id' => [$category->id],
    'brand_id' => $brand->id,
    'type' => 'simple',
    'status' => 1,
    'price' => 5000,
    'qty' => 100,
    'is_print_service' => '1', // checkbox checked
    'is_smart_print_enabled' => '1', // checkbox checked
];

echo "Data request yang akan diproses:\n";
foreach ($requestData as $key => $value) {
    if (is_array($value)) {
        echo "- {$key}: " . implode(', ', $value) . "\n";
    } else {
        echo "- {$key}: {$value}\n";
    }
}
echo "\n";

// Simulasi request object
$request = new \Illuminate\Http\Request($requestData);

// Test explicit checkbox handling yang baru
echo "=== TEST EXPLICIT CHECKBOX HANDLING ===\n";
echo "request->has('is_print_service'): " . ($request->has('is_print_service') ? 'true' : 'false') . "\n";
echo "request->has('is_smart_print_enabled'): " . ($request->has('is_smart_print_enabled') ? 'true' : 'false') . "\n";
echo "request->get('is_print_service'): " . $request->get('is_print_service', 'null') . "\n";
echo "request->get('is_smart_print_enabled'): " . $request->get('is_smart_print_enabled', 'null') . "\n";
echo "\n";

// Simulasi bagian store method yang baru
$data = $requestData;
$data['is_print_service'] = $request->has('is_print_service');
$data['is_smart_print_enabled'] = $request->has('is_smart_print_enabled');

echo "Data setelah explicit checkbox handling:\n";
echo "- is_print_service: " . ($data['is_print_service'] ? 'true' : 'false') . "\n";
echo "- is_smart_print_enabled: " . ($data['is_smart_print_enabled'] ? 'true' : 'false') . "\n";
echo "\n";

// Buat produk untuk test
echo "=== MEMBUAT PRODUK TEST ===\n";
try {
    $product = Product::create([
        'name' => $data['name'],
        'slug' => $data['slug'],
        'sku' => 'TEST-CHECKBOX-' . time(),
        'description' => $data['description'],
        'brand_id' => $data['brand_id'],
        'user_id' => $user->id,
        'type' => $data['type'],
        'status' => $data['status'],
        'price' => $data['price'],
        'is_print_service' => $data['is_print_service'],
        'is_smart_print_enabled' => $data['is_smart_print_enabled'],
    ]);
    
    echo "✓ Produk berhasil dibuat dengan ID: {$product->id}\n";
    echo "✓ is_print_service: " . ($product->is_print_service ? 'true' : 'false') . "\n";
    echo "✓ is_smart_print_enabled: " . ($product->is_smart_print_enabled ? 'true' : 'false') . "\n";
    
    // Assign kategori
    $product->categories()->sync([$category->id]);
    echo "✓ Kategori berhasil di-assign\n";
    
    // Simulasi auto-create variants
    echo "\n=== SIMULASI AUTO-CREATE VARIANTS ===\n";
    if ($product->is_smart_print_enabled) {
        echo "✓ Smart print enabled, akan membuat variants BW dan Color\n";
        
        // Check apakah sudah ada variants
        $existingVariants = ProductVariant::where('product_id', $product->id)->count();
        echo "Existing variants: {$existingVariants}\n";
        
        if ($existingVariants == 0) {
            // Buat BW variant
            $bwVariant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $product->slug . '-bw',
                'name' => $product->name . ' - BW',
                'price' => $product->price,
                'stock' => 0,
                'is_active' => true,
                'print_type' => 'BW',
            ]);
            echo "✓ BW Variant created: ID {$bwVariant->id}\n";
            
            // Buat Color variant
            $colorVariant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $product->slug . '-color',
                'name' => $product->name . ' - Color',
                'price' => $product->price * 1.5,
                'stock' => 0,
                'is_active' => true,
                'print_type' => 'Color',
            ]);
            echo "✓ Color Variant created: ID {$colorVariant->id}\n";
        }
    } else {
        echo "✗ Smart print disabled, tidak akan membuat variants\n";
    }
    
    // Check final state
    echo "\n=== FINAL STATE CHECK ===\n";
    $product->refresh();
    $variantCount = ProductVariant::where('product_id', $product->id)->where('is_active', true)->count();
    
    echo "Produk: {$product->name}\n";
    echo "- is_print_service: " . ($product->is_print_service ? 'true' : 'false') . "\n";
    echo "- is_smart_print_enabled: " . ($product->is_smart_print_enabled ? 'true' : 'false') . "\n";
    echo "- status: {$product->status}\n";
    echo "- active variants: {$variantCount}\n";
    
    // Check apakah akan muncul di stock management
    $shouldAppear = $product->is_print_service && 
                   $product->status == 1 && 
                   $variantCount > 0;
                   
    echo "\nAkan muncul di Stock Management: " . ($shouldAppear ? '✓ YA' : '✗ TIDAK') . "\n";
    
    if ($shouldAppear) {
        echo "✓ Produk memenuhi semua kriteria untuk muncul di admin print service stock!\n";
    } else {
        echo "✗ Produk tidak memenuhi kriteria:\n";
        if (!$product->is_print_service) echo "  - is_print_service: false\n";
        if ($product->status != 1) echo "  - status: not active\n";
        if ($variantCount == 0) echo "  - variants: none\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST SELESAI ===\n";