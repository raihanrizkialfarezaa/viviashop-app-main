<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;

echo "=== FIXING A3 COLOR VARIANT VISIBILITY ===\n\n";

echo "1. CHECKING PRODUCT 135 VARIANTS...\n";

$product135 = Product::find(135);
$allVariants135 = ProductVariant::where('product_id', 135)->get();

echo "All variants for product 135:\n";
foreach ($allVariants135 as $variant) {
    echo "- ID {$variant->id}: {$variant->paper_size} {$variant->print_type}, Stock: {$variant->stock}, Active: " . ($variant->is_active ? 'YES' : 'NO') . "\n";
}

echo "\n2. CHECKING A3 COLOR VARIANT 60...\n";

$variant60 = ProductVariant::find(60);
if ($variant60) {
    echo "Variant 60 details:\n";
    echo "- Product ID: {$variant60->product_id}\n";
    echo "- Paper Size: {$variant60->paper_size}\n";
    echo "- Print Type: {$variant60->print_type}\n";
    echo "- Stock: {$variant60->stock}\n";
    echo "- Active: " . ($variant60->is_active ? 'YES' : 'NO') . "\n";
    
    if ($variant60->product_id != 135) {
        echo "Moving variant 60 to product 135...\n";
        $variant60->update(['product_id' => 135]);
        echo "✅ Variant 60 moved to product 135\n";
    }
    
    if (!$variant60->is_active) {
        echo "Activating variant 60...\n";
        $variant60->update(['is_active' => true]);
        echo "✅ Variant 60 activated\n";
    }
}

echo "\n3. CLEANING UP OTHER PRODUCTS...\n";

$otherProducts = Product::where('is_print_service', true)
    ->where('id', '!=', 135)
    ->get();

foreach ($otherProducts as $product) {
    echo "Deactivating product {$product->id}: {$product->name}\n";
    $product->update(['status' => 0]);
    
    $variants = ProductVariant::where('product_id', $product->id)->get();
    foreach ($variants as $variant) {
        $variant->update(['is_active' => false]);
    }
}

echo "\n4. FINAL VERIFICATION...\n";

use App\Services\PrintService;
$printService = new PrintService();

$frontendProducts = $printService->getPrintProducts();
echo "Frontend shows " . $frontendProducts->count() . " active products\n";

foreach ($frontendProducts as $product) {
    echo "Product: {$product->name} (ID: {$product->id})\n";
    echo "Active variants: " . $product->activeVariants->count() . "\n";
    
    foreach ($product->activeVariants as $variant) {
        echo "  - {$variant->paper_size} {$variant->print_type}: {$variant->stock} stock\n";
    }
}

if ($frontendProducts->first()->activeVariants->count() == 6) {
    echo "\n✅ ALL 6 VARIANTS NOW VISIBLE IN FRONTEND\n";
} else {
    echo "\n❌ Still missing variants\n";
}

echo "\n5. ADMIN STOCK MANAGEMENT CHECK...\n";

use App\Services\StockManagementService;
$stockService = new StockManagementService();
$adminVariants = $stockService->getVariantsByStock('asc');

echo "Admin stock management shows " . $adminVariants->count() . " variants:\n";
foreach ($adminVariants as $variant) {
    echo "- {$variant->paper_size} {$variant->print_type}: {$variant->stock} stock (Product: {$variant->product_id})\n";
}

echo "\n=== A3 COLOR VARIANT FIX COMPLETE ===\n";
