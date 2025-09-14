<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING UPDATED PURCHASE STOCK LOGIC ===\n";

// Test with purchase ID 12 (has AMPLOP)
$purchaseId = 12;
echo "Testing with Purchase ID: {$purchaseId}\n";

$controller = new \App\Http\Controllers\PembelianDetailController();
$response = $controller->getRealtimeStock($purchaseId);
$data = $response->getData(true);

echo "Response received successfully\n";
echo "Number of products in response: " . count($data) . "\n";

// Check for AMPLOP (product ID 9)
if (isset($data[9])) {
    echo "\nAMPLOP (ID 9) data (UPDATED LOGIC):\n";
    echo "- Type: " . $data[9]['type'] . "\n";
    echo "- Original Stock: " . $data[9]['original_stock'] . "\n";
    echo "- Purchased Qty: " . ($data[9]['purchased_qty'] ?? 'N/A') . "\n";
    echo "- Projected Stock: " . ($data[9]['projected_stock'] ?? 'N/A') . "\n";
    echo "- Reserved Qty (compat): " . ($data[9]['reserved_qty'] ?? 'N/A') . "\n";
    echo "- Available Stock (compat): " . ($data[9]['available_stock'] ?? 'N/A') . "\n";
    echo "- Full JSON: " . json_encode($data[9]) . "\n";
    
    // Check if we have purchased items for this product
    $purchaseDetails = \App\Models\PembelianDetail::where('id_pembelian', $purchaseId)
                                                  ->where('id_produk', 9)
                                                  ->get();
    
    echo "\nPurchase details for AMPLOP:\n";
    foreach ($purchaseDetails as $detail) {
        echo "- Detail ID: {$detail->id}, Qty: {$detail->jumlah}\n";
    }
    
    $totalPurchased = $purchaseDetails->sum('jumlah');
    echo "- Total purchased: {$totalPurchased}\n";
    
    if ($totalPurchased > 0) {
        $expectedProjected = $data[9]['original_stock'] + $totalPurchased;
        echo "- Expected projected stock: {$expectedProjected}\n";
        
        if (($data[9]['projected_stock'] ?? $data[9]['available_stock']) == $expectedProjected) {
            echo "✅ Purchase logic working correctly!\n";
            echo "   Original: {$data[9]['original_stock']} + Purchased: {$totalPurchased} = Projected: {$expectedProjected}\n";
        } else {
            echo "❌ Purchase logic incorrect\n";
        }
    } else {
        echo "ℹ️ No items purchased yet for this product\n";
    }
    
} else {
    echo "❌ AMPLOP (ID 9) not found in response\n";
}

echo "\n=== TESTING SCENARIO: Add item to purchase ===\n";

// Check current AMPLOP stock
$amplop = \App\Models\Product::find(9);
$originalStock = $amplop->productInventory ? $amplop->productInventory->qty : 0;
echo "AMPLOP original stock in database: {$originalStock}\n";

// Check existing purchase details
$existingDetails = \App\Models\PembelianDetail::where('id_pembelian', $purchaseId)
                                              ->where('id_produk', 9)
                                              ->sum('jumlah');
echo "Already purchased in this transaction: {$existingDetails}\n";

// Create a test purchase detail (simulate adding 2 more AMPLOP)
$testDetail = \App\Models\PembelianDetail::create([
    'id_pembelian' => $purchaseId,
    'id_produk' => 9,
    'variant_id' => null,
    'harga_beli' => 5000,
    'jumlah' => 2,
    'subtotal' => 10000
]);

echo "✅ Added 2 more AMPLOP to purchase\n";

// Test API again
$response = $controller->getRealtimeStock($purchaseId);
$data = $response->getData(true);

if (isset($data[9])) {
    echo "\nAMPLOP after adding 2 more items:\n";
    echo "- Original Stock: " . $data[9]['original_stock'] . "\n";
    echo "- Purchased Qty: " . ($data[9]['purchased_qty'] ?? $data[9]['reserved_qty']) . "\n";
    echo "- Projected Stock: " . ($data[9]['projected_stock'] ?? $data[9]['available_stock']) . "\n";
    
    $totalPurchased = $existingDetails + 2;
    $expectedProjected = $originalStock + $totalPurchased;
    echo "- Expected: {$originalStock} + {$totalPurchased} = {$expectedProjected}\n";
    
    if (($data[9]['projected_stock'] ?? $data[9]['available_stock']) == $expectedProjected) {
        echo "✅ Purchase stock calculation working perfectly!\n";
        echo "   Now shows INCREASED stock due to purchase: {$originalStock} → {$expectedProjected}\n";
    } else {
        echo "❌ Calculation still wrong\n";
    }
}

// Cleanup
$testDetail->delete();
echo "\n✅ Test data cleaned up\n";