<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== CLEANING UP AMPLOP STOCK ISSUE ===\n";

$amplop = \App\Models\Product::with('productInventory')->find(9);
if (!$amplop) {
    echo "❌ AMPLOP product not found!\n";
    exit;
}

echo "Current AMPLOP stock: {$amplop->productInventory->qty}\n";

// Remove duplicate RekamanStok entries
echo "\n=== REMOVING DUPLICATE REKAMAN STOK ===\n";
$duplicateRekamanStok = \App\Models\RekamanStok::where('product_id', 9)
                         ->whereDate('created_at', today())
                         ->orderBy('created_at', 'desc')
                         ->get();

if ($duplicateRekamanStok->count() > 1) {
    echo "Found {$duplicateRekamanStok->count()} entries for today\n";
    
    // Keep the first one, delete the rest
    $keepFirst = $duplicateRekamanStok->first();
    $toDelete = $duplicateRekamanStok->skip(1);
    
    echo "Keeping entry ID {$keepFirst->id} (created at {$keepFirst->created_at})\n";
    echo "Deleting " . $toDelete->count() . " duplicate entries...\n";
    
    foreach ($toDelete as $duplicate) {
        echo "- Deleting entry ID {$duplicate->id}\n";
        $duplicate->delete();
    }
}

// Reset stock to correct value
echo "\n=== CORRECTING STOCK QUANTITY ===\n";
echo "Original stock should be: 10\n";
echo "Purchased quantity: 10\n";
echo "Expected final stock: 20\n";
echo "Current stock: {$amplop->productInventory->qty}\n";

// Set correct stock
$amplop->productInventory->qty = 20;
$amplop->productInventory->save();

echo "✅ Stock corrected to: {$amplop->productInventory->qty}\n";

// Create default variant for AMPLOP so StockMovement can work
echo "\n=== CREATING DEFAULT VARIANT FOR AMPLOP ===\n";
$existingVariant = \App\Models\ProductVariant::where('product_id', 9)->first();

if ($existingVariant) {
    echo "Variant already exists: ID {$existingVariant->id}\n";
    $existingVariant->stock = 20;
    $existingVariant->save();
    echo "Updated variant stock to: {$existingVariant->stock}\n";
} else {
    $variant = \App\Models\ProductVariant::create([
        'product_id' => 9,
        'sku' => 'AMPLOP-DEFAULT',
        'name' => 'AMPLOP (Default)',
        'price' => $amplop->price ?? 0,
        'harga_beli' => $amplop->harga_beli ?? 0,
        'stock' => 20
    ]);
    echo "✅ Created default variant: ID {$variant->id}\n";
}

echo "\n=== CLEANUP COMPLETE ===\n";
echo "AMPLOP now has:\n";
echo "- Correct stock quantity: 20\n";
echo "- Default variant for StockMovement tracking\n";
echo "- No duplicate RekamanStok entries\n";