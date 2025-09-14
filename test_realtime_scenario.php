<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== REALTIME STOCK SCENARIO TEST ===\n";

try {
    // Step 1: Create test purchase
    $supplier = \App\Models\Supplier::first();
    if (!$supplier) {
        $supplier = \App\Models\Supplier::create([
            'nama' => 'Test Supplier',
            'alamat' => 'Test Address', 
            'telepon' => '08123456789'
        ]);
        echo "Created test supplier\n";
    }
    
    $purchase = \App\Models\Pembelian::create([
        'id_supplier' => $supplier->id,
        'total_item' => 0,
        'total_harga' => 0,
        'diskon' => 0,
        'bayar' => 0
    ]);
    
    echo "Created purchase ID: {$purchase->id}\n";
    
    // Step 2: Check AMPLOP initial stock
    $amplop = \App\Models\Product::find(9);
    if (!$amplop) {
        echo "AMPLOP product not found!\n";
        exit;
    }
    
    $initialStock = $amplop->productInventory ? $amplop->productInventory->qty : 0;
    echo "AMPLOP initial stock: {$initialStock}\n";
    
    // Step 3: Test API before adding any item
    $controller = new \App\Http\Controllers\PembelianDetailController();
    $response = $controller->getRealtimeStock($purchase->id);
    $data = $response->getData(true);
    
    if (isset($data[9])) {
        $stock = $data[9];
        echo "Before adding item:\n";
        echo "- Original: {$stock['original_stock']}\n";
        echo "- Reserved: {$stock['reserved_qty']}\n"; 
        echo "- Available: {$stock['available_stock']}\n";
    } else {
        echo "No stock data for AMPLOP before adding\n";
    }
    
    // Step 4: Add AMPLOP to purchase
    $detail = \App\Models\PembelianDetail::create([
        'id_pembelian' => $purchase->id,
        'id_produk' => 9,
        'variant_id' => null,
        'harga_beli' => $amplop->harga_beli ?? 5000,
        'jumlah' => 1,
        'subtotal' => ($amplop->harga_beli ?? 5000) * 1
    ]);
    
    echo "\nAdded 1 unit of AMPLOP to purchase\n";
    
    // Step 5: Test API after adding item
    $response = $controller->getRealtimeStock($purchase->id);
    $data = $response->getData(true);
    
    if (isset($data[9])) {
        $stock = $data[9];
        echo "After adding item:\n";
        echo "- Original: {$stock['original_stock']}\n";
        echo "- Reserved: {$stock['reserved_qty']}\n";
        echo "- Available: {$stock['available_stock']}\n";
        
        $expected = $initialStock - 1;
        if ($stock['available_stock'] == $expected) {
            echo "✓ Calculation correct\n";
        } else {
            echo "✗ Expected: {$expected}, Got: {$stock['available_stock']}\n";
        }
        
        // Step 6: Generate the exact JSON that frontend should receive
        echo "\nJSON Response for frontend:\n";
        echo json_encode($data, JSON_PRETTY_PRINT);
        
    } else {
        echo "No stock data for AMPLOP after adding\n";
    }
    
    // Step 7: Test URL endpoint that frontend calls
    echo "\nTesting URL endpoint: /admin/pembelian_detail/realtime-stock/{$purchase->id}\n";
    echo "This is the URL that the JavaScript fetchRealtimeStock() calls\n";
    
    // Cleanup
    $detail->delete();
    $purchase->delete();
    echo "\n✓ Cleanup complete\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}