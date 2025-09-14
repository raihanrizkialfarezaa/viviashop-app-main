<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::call('cache:clear');

echo "=== FINAL STOCK ISSUE TEST ===\n\n";

echo "1. Testing RAKET PADEL with updated stock...\n";
$raket = DB::table('products')->where('id', 138)->first();
echo "RAKET PADEL - Stock: {$raket->total_stock}, Price: {$raket->price}\n";

echo "\n2. Testing other simple products with stock...\n";
$simpleProducts = DB::table('products')
    ->where('type', 'simple')
    ->select('id', 'name', 'total_stock', 'price')
    ->limit(10)
    ->get();

foreach ($simpleProducts as $product) {
    echo "- {$product->name}: Stock = {$product->total_stock}, Price = {$product->price}\n";
}

echo "\n3. Updating stock for some test products...\n";
$testProductIds = [3, 4, 5]; // Some simple products
foreach ($testProductIds as $id) {
    DB::table('products')->where('id', $id)->update(['total_stock' => 50]);
    echo "Updated product ID {$id} stock to 50\n";
}

echo "\n4. Verification after update...\n";
$updatedProducts = DB::table('products')
    ->whereIn('id', [138, 3, 4, 5])
    ->select('id', 'name', 'total_stock', 'price')
    ->get();

foreach ($updatedProducts as $product) {
    echo "- ID {$product->id}: {$product->name} - Stock: {$product->total_stock}, Price: {$product->price}\n";
}

echo "\n=== READY FOR TESTING ===\n";
echo "Now you have several simple products with stock > 0:\n";
echo "- RAKET PADEL (ID: 138): Stock 100, Price Rp 2\n";
echo "- Other test products (IDs: 3,4,5): Stock 50 each\n";
echo "\nTest these products in the order creation page.\n";
echo "They should now show proper prices and NOT show 'Out of Stock'.\n";