<?php

use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Product;

echo "=== REALTIME STOCK DEBUG TEST ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Check current stock of AMPLOP (ID 9)
$amplop = Product::find(9);
if ($amplop) {
    echo "AMPLOP Product Found:\n";
    echo "- ID: {$amplop->id}\n";
    echo "- Name: {$amplop->name}\n";
    echo "- Type: {$amplop->type}\n";
    
    if ($amplop->productInventory) {
        echo "- Current Stock: {$amplop->productInventory->qty}\n";
    } else {
        echo "- No inventory record found\n";
    }
} else {
    echo "AMPLOP product not found\n";
    exit;
}

// Find active purchase sessions
echo "\n=== ACTIVE PURCHASE SESSIONS ===\n";
$activePurchases = Pembelian::whereNull('waktu')->get();
echo "Found " . $activePurchases->count() . " active purchase sessions:\n";

foreach ($activePurchases as $purchase) {
    echo "- Purchase ID: {$purchase->id}, Supplier: " . ($purchase->supplier->nama ?? 'Unknown') . "\n";
    
    $details = PembelianDetail::where('id_pembelian', $purchase->id)->get();
    echo "  Items in this purchase:\n";
    
    foreach ($details as $detail) {
        $product = Product::find($detail->id_produk);
        echo "    - Product: " . ($product->name ?? 'Unknown') . " (ID: {$detail->id_produk}), Qty: {$detail->jumlah}\n";
    }
}

// Test realtime stock calculation for AMPLOP
echo "\n=== REALTIME STOCK CALCULATION TEST ===\n";

if ($activePurchases->count() > 0) {
    $testPurchaseId = $activePurchases->first()->id;
    echo "Testing with Purchase ID: {$testPurchaseId}\n";
    
    // Manual calculation
    $reservedQty = PembelianDetail::where('id_pembelian', $testPurchaseId)
                                 ->where('id_produk', 9)
                                 ->whereNull('variant_id')
                                 ->sum('jumlah');
    
    $originalStock = $amplop->productInventory ? $amplop->productInventory->qty : 0;
    $availableStock = $originalStock - $reservedQty;
    
    echo "Manual Calculation:\n";
    echo "- Original Stock: {$originalStock}\n";
    echo "- Reserved Qty: {$reservedQty}\n";
    echo "- Available Stock: {$availableStock}\n";
    
    // Test controller method
    try {
        $controller = new \App\Http\Controllers\PembelianDetailController();
        $response = $controller->getRealtimeStock($testPurchaseId);
        $data = $response->getData(true);
        
        if (isset($data[9])) {
            $stockData = $data[9];
            echo "\nController Response:\n";
            echo "- Original Stock: {$stockData['original_stock']}\n";
            echo "- Reserved Qty: {$stockData['reserved_qty']}\n";
            echo "- Available Stock: {$stockData['available_stock']}\n";
            
            if ($stockData['available_stock'] == $availableStock) {
                echo "✓ Calculation matches\n";
            } else {
                echo "✗ Calculation mismatch\n";
            }
        } else {
            echo "✗ AMPLOP data not found in controller response\n";
            echo "Available products in response: " . implode(', ', array_keys($data)) . "\n";
        }
    } catch (Exception $e) {
        echo "✗ Controller test failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "No active purchases found for testing\n";
}

// Check JavaScript integration
echo "\n=== JAVASCRIPT INTEGRATION CHECK ===\n";
$viewFile = resource_path('views/admin/pembelian_detail/index.blade.php');
if (file_exists($viewFile)) {
    $content = file_get_contents($viewFile);
    
    $jsChecks = [
        'fetchRealtimeStock()' => 'Function definition',
        'updateStockDisplay()' => 'Update display function',
        'realtimeStockData' => 'Global variable',
        'fetchRealtimeStock();' => 'Function call in tampilProduk',
        'table.ajax.reload(() => {' => 'AJAX reload integration'
    ];
    
    foreach ($jsChecks as $pattern => $description) {
        if (strpos($content, $pattern) !== false) {
            echo "✓ {$description}\n";
        } else {
            echo "✗ {$description}\n";
        }
    }
} else {
    echo "✗ View file not found\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>