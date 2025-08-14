<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\ProductInventory;

$product = Product::with('productInventory')->find(117);
echo "Product 117: {$product->name}\n";
echo "Has inventory: " . ($product->productInventory ? 'YES' : 'NO') . "\n";

if (!$product->productInventory) {
    echo "Creating inventory...\n";
    ProductInventory::create([
        'product_id' => 117,
        'qty' => 100
    ]);
    echo "✅ Inventory created\n";
}

$variants = $product->variants;
foreach ($variants as $variant) {
    echo "Variant {$variant->id}: {$variant->name}\n";
    $variantWithInventory = Product::with('productInventory')->find($variant->id);
    if (!$variantWithInventory->productInventory) {
        echo "  Creating variant inventory...\n";
        ProductInventory::create([
            'product_id' => $variant->id,
            'qty' => 100
        ]);
        echo "  ✅ Variant inventory created\n";
    } else {
        echo "  Has inventory: {$variantWithInventory->productInventory->qty}\n";
    }
}
