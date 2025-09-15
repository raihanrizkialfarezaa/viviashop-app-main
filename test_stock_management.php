<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\StockManagementService;

echo "Testing Stock Management Service...\n";

$stockService = new StockManagementService();

echo "Mengambil variants yang akan ditampilkan di stock management:\n";
$variants = $stockService->getVariantsByStock('asc');

echo "Total variants ditemukan: " . $variants->count() . "\n\n";

foreach ($variants as $variant) {
    echo "=== Variant Info ===\n";
    echo "ID: " . $variant->id . "\n";
    echo "Name: " . $variant->name . "\n";
    echo "Product: " . $variant->product->name . "\n";
    echo "Paper Size: " . $variant->paper_size . "\n";
    echo "Print Type: " . $variant->print_type . "\n";
    echo "Stock: " . $variant->stock . "\n";
    echo "Is Active: " . ($variant->is_active ? 'YES' : 'NO') . "\n";
    echo "Product Print Service: " . ($variant->product->is_print_service ? 'YES' : 'NO') . "\n";
    echo "Product Smart Print: " . ($variant->product->is_smart_print_enabled ? 'YES' : 'NO') . "\n";
    echo "Product Status: " . $variant->product->status . "\n";
    echo "---\n\n";
}