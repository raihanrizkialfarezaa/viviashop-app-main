<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\Supplier;
use App\Models\ProductVariant;

echo "=== REALTIME STOCK UPDATE SYSTEM TEST ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Find or create test supplier
$supplier = Supplier::first();
if (!$supplier) {
    $supplier = Supplier::create([
        'nama' => 'Test Supplier',
        'alamat' => 'Test Address',
        'telepon' => '08123456789'
    ]);
}

// Find or create test products
$simpleProduct = Product::where('type', 'simple')->first();
if (!$simpleProduct) {
    $simpleProduct = Product::create([
        'name' => 'Test Simple Product - AMPLOP',
        'type' => 'simple',
        'status' => 1,
        'harga_beli' => 5000,
        'price' => 10000
    ]);
    
    ProductInventory::create([
        'product_id' => $simpleProduct->id,
        'qty' => 100
    ]);
}

$configurableProduct = Product::where('type', 'configurable')->first();
if (!$configurableProduct) {
    $configurableProduct = Product::create([
        'name' => 'Test Configurable Product',
        'type' => 'configurable',
        'status' => 1,
        'harga_beli' => 15000,
        'price' => 25000
    ]);
    
    // Create variants for configurable product
    $variant1 = ProductVariant::create([
        'product_id' => $configurableProduct->id,
        'harga_beli' => 15000,
        'price' => 25000,
        'stock' => 50,
        'is_active' => true
    ]);
    
    $variant2 = ProductVariant::create([
        'product_id' => $configurableProduct->id,
        'harga_beli' => 16000,
        'price' => 26000,
        'stock' => 30,
        'is_active' => true
    ]);
}

echo "✓ Test products prepared\n";
echo "  - Simple Product ID: {$simpleProduct->id}, Stock: " . ($simpleProduct->productInventory->qty ?? 0) . "\n";
echo "  - Configurable Product ID: {$configurableProduct->id}\n";

// Create test purchase
$pembelian = Pembelian::create([
    'id_supplier' => $supplier->id,
    'total_item' => 0,
    'total_harga' => 0,
    'diskon' => 0,
    'bayar' => 0,
    'status' => 'pending'
]);

echo "  - Test Purchase ID: {$pembelian->id}\n\n";

// Test 1: Add simple product to purchase
echo "=== TEST 1: Add Simple Product ===\n";
$originalStock = $simpleProduct->productInventory->qty;
echo "Original stock: {$originalStock}\n";

// Simulate adding 10 units
$detail1 = PembelianDetail::create([
    'id_pembelian' => $pembelian->id,
    'id_produk' => $simpleProduct->id,
    'variant_id' => null,
    'harga_beli' => $simpleProduct->harga_beli,
    'jumlah' => 10,
    'subtotal' => $simpleProduct->harga_beli * 10
]);

echo "Added 10 units to purchase\n";
echo "Expected available stock: " . ($originalStock - 10) . "\n";

// Test endpoint
$url = "http://127.0.0.1:8000/admin/pembelian_detail/realtime-stock/{$pembelian->id}";
echo "Testing endpoint: {$url}\n";

try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'User-Agent: PHP-Test-Script'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        $simpleStockData = $data[$simpleProduct->id] ?? null;
        
        if ($simpleStockData) {
            echo "✓ API Response successful\n";
            echo "  - Original Stock: {$simpleStockData['original_stock']}\n";
            echo "  - Reserved Qty: {$simpleStockData['reserved_qty']}\n";
            echo "  - Available Stock: {$simpleStockData['available_stock']}\n";
            
            if ($simpleStockData['reserved_qty'] == 10 && $simpleStockData['available_stock'] == ($originalStock - 10)) {
                echo "✓ TEST 1 PASSED: Stock calculation correct\n";
            } else {
                echo "✗ TEST 1 FAILED: Stock calculation incorrect\n";
            }
        } else {
            echo "✗ TEST 1 FAILED: Product data not found in response\n";
        }
    } else {
        echo "✗ TEST 1 FAILED: API returned HTTP {$httpCode}\n";
        echo "Response: {$response}\n";
    }
} catch (Exception $e) {
    echo "✗ TEST 1 FAILED: Exception - " . $e->getMessage() . "\n";
}

echo "\n=== TEST 2: Update Quantity ===\n";

// Update quantity to 5
$detail1->update(['jumlah' => 5, 'subtotal' => $detail1->harga_beli * 5]);
echo "Updated quantity from 10 to 5\n";
echo "Expected available stock: " . ($originalStock - 5) . "\n";

try {
    $response = curl_exec($ch = curl_init());
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        $simpleStockData = $data[$simpleProduct->id] ?? null;
        
        if ($simpleStockData && $simpleStockData['reserved_qty'] == 5) {
            echo "✓ TEST 2 PASSED: Stock updated correctly after quantity change\n";
        } else {
            echo "✗ TEST 2 FAILED: Stock not updated correctly\n";
        }
    }
} catch (Exception $e) {
    echo "✗ TEST 2 FAILED: Exception - " . $e->getMessage() . "\n";
}

echo "\n=== TEST 3: Add Configurable Product Variant ===\n";

$variants = ProductVariant::where('product_id', $configurableProduct->id)->get();
if ($variants->count() > 0) {
    $firstVariant = $variants->first();
    $originalVariantStock = $firstVariant->stock;
    
    echo "Adding variant {$firstVariant->id} with stock {$originalVariantStock}\n";
    
    // Add variant to purchase
    $detail2 = PembelianDetail::create([
        'id_pembelian' => $pembelian->id,
        'id_produk' => $configurableProduct->id,
        'variant_id' => $firstVariant->id,
        'harga_beli' => $firstVariant->harga_beli,
        'jumlah' => 3,
        'subtotal' => $firstVariant->harga_beli * 3
    ]);
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            $configurableStockData = $data[$configurableProduct->id] ?? null;
            
            if ($configurableStockData && isset($configurableStockData['variants'][$firstVariant->id])) {
                $variantStockData = $configurableStockData['variants'][$firstVariant->id];
                echo "  - Variant Original Stock: {$variantStockData['original_stock']}\n";
                echo "  - Variant Reserved Qty: {$variantStockData['reserved_qty']}\n";
                echo "  - Variant Available Stock: {$variantStockData['available_stock']}\n";
                
                if ($variantStockData['reserved_qty'] == 3) {
                    echo "✓ TEST 3 PASSED: Variant stock calculation correct\n";
                } else {
                    echo "✗ TEST 3 FAILED: Variant stock calculation incorrect\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "✗ TEST 3 FAILED: Exception - " . $e->getMessage() . "\n";
    }
}

echo "\n=== TEST 4: Delete Item ===\n";

// Delete the simple product item
$detail1->delete();
echo "Deleted simple product item\n";
echo "Expected available stock: {$originalStock} (back to original)\n";

try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        $simpleStockData = $data[$simpleProduct->id] ?? null;
        
        if ($simpleStockData && $simpleStockData['reserved_qty'] == 0) {
            echo "✓ TEST 4 PASSED: Stock restored after deletion\n";
        } else {
            echo "✗ TEST 4 FAILED: Stock not restored after deletion\n";
        }
    }
} catch (Exception $e) {
    echo "✗ TEST 4 FAILED: Exception - " . $e->getMessage() . "\n";
}

// Cleanup
echo "\n=== CLEANUP ===\n";
PembelianDetail::where('id_pembelian', $pembelian->id)->delete();
$pembelian->delete();
echo "✓ Test data cleaned up\n";

echo "\n=== SYSTEM VALIDATION ===\n";

// Check if necessary files exist and contain required functions
$controllerFile = __DIR__ . '/app/Http/Controllers/PembelianDetailController.php';
$viewFile = __DIR__ . '/resources/views/admin/pembelian_detail/index.blade.php';
$routeFile = __DIR__ . '/routes/web.php';

$checks = [
    'Controller endpoint' => file_exists($controllerFile) && strpos(file_get_contents($controllerFile), 'getRealtimeStock'),
    'Route defined' => file_exists($routeFile) && strpos(file_get_contents($routeFile), 'realtime-stock'),
    'JavaScript functions' => file_exists($viewFile) && strpos(file_get_contents($viewFile), 'fetchRealtimeStock'),
    'Update stock display' => file_exists($viewFile) && strpos(file_get_contents($viewFile), 'updateStockDisplay')
];

foreach ($checks as $check => $result) {
    echo ($result ? "✓" : "✗") . " {$check}\n";
}

echo "\n=== SUMMARY ===\n";
echo "Realtime stock update system implemented with:\n";
echo "1. Backend API endpoint for fetching current stock minus reserved quantities\n";
echo "2. Frontend JavaScript functions to update modal display\n";
echo "3. Integration with all CRUD operations (Add, Update, Delete)\n";
echo "4. Support for both simple and configurable products\n";
echo "5. Visual indicators for reserved stock and availability\n";

echo "\n=== USAGE INSTRUCTIONS ===\n";
echo "1. Open: http://127.0.0.1:8000/admin/pembelian_detail\n";
echo "2. Add products (e.g., AMPLOP ID 9)\n";
echo "3. Open product modal again - stock will reflect reserved quantities\n";
echo "4. Modify quantities - stock updates in real-time\n";
echo "5. Delete items - stock is restored immediately\n";

echo "\n=== TEST COMPLETE ===\n";
?>