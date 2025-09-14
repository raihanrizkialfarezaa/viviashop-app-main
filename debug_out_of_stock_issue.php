<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::call('cache:clear');

echo "=== DEBUG OUT OF STOCK ISSUE ===\n\n";

echo "1. Checking RAKET PADEL stock in database...\n";
$raket = DB::table('products')->where('id', 138)->first();

echo "Database values:\n";
echo "- total_stock: {$raket->total_stock} (type: " . gettype($raket->total_stock) . ")\n";
echo "- price: {$raket->price} (type: " . gettype($raket->price) . ")\n";
echo "- name: {$raket->name}\n";
echo "- type: {$raket->type}\n";

echo "\n2. Simulating frontend data flow...\n";

$productSelectionData = [
    'id' => $raket->id,
    'name' => $raket->name,
    'sku' => $raket->sku,
    'price' => $raket->price,
    'type' => $raket->type,
    'total_stock' => $raket->total_stock
];

echo "Product data that would be passed to frontend:\n";
echo json_encode($productSelectionData, JSON_PRETTY_PRINT) . "\n";

echo "\n3. Checking data types and values...\n";
$stock = $productSelectionData['total_stock'] ?? $productSelectionData['stock'] ?? 0;
echo "Stock value: {$stock}\n";
echo "Stock type: " . gettype($stock) . "\n";
echo "Stock == 0: " . ($stock == 0 ? 'true' : 'false') . "\n";
echo "Stock === 0: " . ($stock === 0 ? 'true' : 'false') . "\n";
echo "Stock === '0': " . ($stock === '0' ? 'true' : 'false') . "\n";

echo "\n4. Testing JavaScript equivalent checks...\n";
if ($stock == 0) {
    echo "❌ Stock would be marked as out of stock (loose equality)\n";
} else {
    echo "✅ Stock should be available (loose equality)\n";
}

if ($stock === 0) {
    echo "❌ Stock would be marked as out of stock (strict equality)\n";
} else {
    echo "✅ Stock should be available (strict equality)\n";
}

echo "\n5. Checking other simple products...\n";
$otherSimpleProducts = DB::table('products')
    ->where('type', 'simple')
    ->where('id', '!=', 138)
    ->select('id', 'name', 'total_stock')
    ->limit(3)
    ->get();

foreach ($otherSimpleProducts as $product) {
    echo "- {$product->name}: stock = {$product->total_stock} (type: " . gettype($product->total_stock) . ")\n";
}

echo "\n=== ISSUE IDENTIFIED ===\n";
echo "The problem might be in how JavaScript handles the stock value comparison.\n";
echo "Database returns: " . gettype($raket->total_stock) . " value\n";
echo "JavaScript might be receiving it as different type causing === 0 to fail.\n";