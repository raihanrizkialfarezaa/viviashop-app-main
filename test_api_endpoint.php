<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING REALTIME STOCK API ===\n";

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
    echo "\nAMPLOP (ID 9) data:\n";
    echo "- Type: " . $data[9]['type'] . "\n";
    echo "- Original Stock: " . $data[9]['original_stock'] . "\n";
    echo "- Reserved Qty: " . $data[9]['reserved_qty'] . "\n";
    echo "- Available Stock: " . $data[9]['available_stock'] . "\n";
} else {
    echo "❌ AMPLOP (ID 9) not found in response\n";
}

// Sample a few other products
echo "\nSample of other products:\n";
$count = 0;
foreach ($data as $productId => $stockData) {
    if ($count >= 3) break;
    echo "Product {$productId}: Available={$stockData['available_stock']}, Reserved={$stockData['reserved_qty']}\n";
    $count++;
}

echo "\n=== URL CONSTRUCTION TEST ===\n";
$baseUrl = env('APP_URL', 'http://localhost');
$expectedUrl = "{$baseUrl}/admin/pembelian_detail/realtime-stock/{$purchaseId}";
echo "Expected frontend URL: {$expectedUrl}\n";

echo "\n=== ROUTE TESTING ===\n";
// Test if route exists
try {
    $route = \Route::getRoutes()->getByName('admin.pembelian_detail.realtime_stock');
    if ($route) {
        echo "✓ Route 'admin.pembelian_detail.realtime_stock' exists\n";
        echo "Route URI: " . $route->uri() . "\n";
    } else {
        echo "❌ Route not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error checking route: " . $e->getMessage() . "\n";
}