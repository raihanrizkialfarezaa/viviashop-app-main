<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "FIXING UNREALISTIC PRODUCT PRICES\n";
echo "=================================\n\n";

$problematicProduct = App\Models\Product::where('name', 'LIKE', '%JAM DINDING CUSTOM%')->first();

if ($problematicProduct) {
    echo "Found problematic product:\n";
    echo "- Name: {$problematicProduct->name}\n";
    echo "- Current Cost: Rp " . number_format($problematicProduct->harga_beli) . "\n";
    echo "- Current Price: Rp " . number_format($problematicProduct->price) . "\n\n";
    
    $newCostPrice = $problematicProduct->price * 0.6;
    
    echo "Fixing cost price to Rp " . number_format($newCostPrice) . " (60% of selling price)\n";
    
    $problematicProduct->update([
        'harga_beli' => $newCostPrice
    ]);
    
    echo "✓ Product updated successfully!\n\n";
}

$otherProblematic = App\Models\Product::whereRaw('harga_beli > price')->get();

if ($otherProblematic->count() > 0) {
    echo "Found " . $otherProblematic->count() . " other problematic products:\n";
    
    foreach($otherProblematic as $product) {
        echo "- {$product->name}: Cost Rp " . number_format($product->harga_beli) . " > Price Rp " . number_format($product->price) . "\n";
        
        $newCost = $product->price * 0.6;
        $product->update(['harga_beli' => $newCost]);
        echo "  ✓ Fixed to Rp " . number_format($newCost) . "\n";
    }
} else {
    echo "✓ No other problematic products found.\n";
}

echo "\n✓ All unrealistic product prices have been fixed!\n";

?>
