<?php

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

$testPurchaseId = null;
$testProductId = 9; // AMPLOP

echo "=== REALTIME STOCK MANUAL TEST ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Step 1: Find or create test purchase
$supplier = \App\Models\Supplier::first();
if (!$supplier) {
    echo "No supplier found. Creating test supplier...\n";
    $supplier = \App\Models\Supplier::create([
        'nama' => 'Test Supplier',
        'alamat' => 'Test Address',
        'telepon' => '08123456789'
    ]);
}

// Create new test purchase
$purchase = \App\Models\Pembelian::create([
    'id_supplier' => $supplier->id,
    'total_item' => 0,
    'total_harga' => 0,
    'diskon' => 0,
    'bayar' => 0
]);

$testPurchaseId = $purchase->id;
echo "Created test purchase ID: {$testPurchaseId}\n";

// Step 2: Check AMPLOP initial stock
$amplop = \App\Models\Product::find($testProductId);
if (!$amplop) {
    echo "AMPLOP product not found!\n";
    exit;
}

$initialStock = $amplop->productInventory ? $amplop->productInventory->qty : 0;
echo "AMPLOP initial stock: {$initialStock}\n";

// Step 3: Add AMPLOP to purchase (reserve 1 unit)
$detail = \App\Models\PembelianDetail::create([
    'id_pembelian' => $testPurchaseId,
    'id_produk' => $testProductId,
    'variant_id' => null,
    'harga_beli' => $amplop->harga_beli ?? 5000,
    'jumlah' => 1,
    'subtotal' => ($amplop->harga_beli ?? 5000) * 1
]);

echo "Added 1 unit of AMPLOP to purchase\n";

// Step 4: Test realtime stock API
echo "\n=== TESTING REALTIME STOCK API ===\n";

try {
    $controller = new \App\Http\Controllers\PembelianDetailController();
    $response = $controller->getRealtimeStock($testPurchaseId);
    $data = $response->getData(true);
    
    if (isset($data[$testProductId])) {
        $stockData = $data[$testProductId];
        echo "API Response for AMPLOP:\n";
        echo "- Type: {$stockData['type']}\n";
        echo "- Original Stock: {$stockData['original_stock']}\n";
        echo "- Reserved Qty: {$stockData['reserved_qty']}\n";
        echo "- Available Stock: {$stockData['available_stock']}\n";
        
        $expectedAvailable = $initialStock - 1;
        if ($stockData['available_stock'] == $expectedAvailable && $stockData['reserved_qty'] == 1) {
            echo "✓ API calculation correct\n";
        } else {
            echo "✗ API calculation incorrect\n";
            echo "  Expected available: {$expectedAvailable}, got: {$stockData['available_stock']}\n";
            echo "  Expected reserved: 1, got: {$stockData['reserved_qty']}\n";
        }
    } else {
        echo "✗ AMPLOP not found in API response\n";
        echo "Available products: " . implode(', ', array_keys($data)) . "\n";
    }
} catch (Exception $e) {
    echo "✗ API test failed: " . $e->getMessage() . "\n";
}

// Step 5: Test JSON output (simulate AJAX call)
echo "\n=== TESTING JSON ENDPOINT ===\n";
$url = "http://127.0.0.1:8000/admin/pembelian_detail/realtime-stock/{$testPurchaseId}";
echo "URL: {$url}\n";

$json = json_encode($data ?? []);
echo "JSON Response sample:\n";
echo substr($json, 0, 200) . "...\n";

// Step 6: Cleanup
echo "\n=== CLEANUP ===\n";
$detail->delete();
$purchase->delete();
echo "✓ Test data cleaned up\n";

echo "\n=== INSTRUCTIONS ===\n";
echo "If the API is working correctly, the issue is likely in the frontend JavaScript.\n";
echo "Check browser console for:\n";
echo "1. fetchRealtimeStock() being called\n";
echo "2. AJAX request being made\n";
echo "3. Response data structure\n";
echo "4. updateStockDisplay() execution\n";

echo "\n=== TEST COMPLETE ===\n";
?>