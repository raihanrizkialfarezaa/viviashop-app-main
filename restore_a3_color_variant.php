<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StockManagementService;

echo "=== RESTORING MISSING A3 COLOR VARIANT ===\n\n";

echo "1. CHECKING CURRENT VARIANTS...\n";

$product = Product::find(135);
$currentVariants = $product->activeVariants;

echo "Current active variants for product {$product->id}:\n";
foreach ($currentVariants as $variant) {
    echo "- {$variant->paper_size} {$variant->print_type}: {$variant->stock} stock\n";
}

echo "\n2. CHECKING DEACTIVATED A3 COLOR VARIANTS...\n";

$deactivatedA3Color = ProductVariant::where('product_id', 135)
    ->where('paper_size', 'A3')
    ->where('print_type', 'color')
    ->where('is_active', false)
    ->first();

if ($deactivatedA3Color) {
    echo "Found deactivated A3 color variant ID {$deactivatedA3Color->id} with {$deactivatedA3Color->stock} stock\n";
    
    $deactivatedA3Color->update(['is_active' => true]);
    echo "✅ Reactivated A3 color variant\n";
} else {
    $activeA3Color = ProductVariant::where('product_id', 137)
        ->where('paper_size', 'A3')
        ->where('print_type', 'color')
        ->where('is_active', true)
        ->first();
    
    if ($activeA3Color) {
        echo "Found A3 color variant from product 137: ID {$activeA3Color->id} with {$activeA3Color->stock} stock\n";
        
        $activeA3Color->update([
            'product_id' => 135,
            'is_active' => true
        ]);
        echo "✅ Transferred A3 color variant to main product\n";
    }
}

echo "\n3. FINAL VERIFICATION...\n";

$stockService = new StockManagementService();
$finalVariants = $stockService->getVariantsByStock('asc');

echo "Final admin stock management data:\n";
foreach ($finalVariants as $variant) {
    echo "- {$variant->paper_size} {$variant->print_type}: {$variant->stock} stock (ID: {$variant->id})\n";
}

echo "\n4. FRONTEND VERIFICATION...\n";

use App\Services\PrintService;
$printService = new PrintService();

$frontendProducts = $printService->getPrintProducts();
$product = $frontendProducts->first();

echo "Frontend variants:\n";
foreach ($product->activeVariants as $variant) {
    echo "- {$variant->paper_size} {$variant->print_type}: {$variant->stock} stock\n";
}

if ($product->activeVariants->count() == 6) {
    echo "\n✅ ALL 6 VARIANTS RESTORED SUCCESSFULLY\n";
} else {
    echo "\n❌ Missing variants detected - count: " . $product->activeVariants->count() . "\n";
}

echo "\n=== A3 COLOR VARIANT RESTORATION COMPLETE ===\n";
