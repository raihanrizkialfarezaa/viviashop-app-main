<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

$app->boot();

use App\Models\Product;
use App\Models\ProductVariant;

echo "=== DEBUGGING KERTAS MINYAK CONVERSION ERROR ===\n\n";

// Find the problematic product
$product = Product::where('name', 'like', '%kertas minyak%')
                 ->orWhere('sku', 'fskfsfshkh')
                 ->first();

if (!$product) {
    echo "❌ Product 'kertas minyak' not found\n";
    exit;
}

echo "🎯 FOUND PRODUCT: {$product->name} (ID: {$product->id}, SKU: {$product->sku})\n\n";

echo "CURRENT STATUS:\n";
echo "- is_print_service: " . ($product->is_print_service ? 'true' : 'false') . "\n";
echo "- is_smart_print_enabled: " . ($product->is_smart_print_enabled ? 'true' : 'false') . "\n";
echo "- status: {$product->status}\n";

echo "\nCURRENT VARIANTS:\n";
$variants = $product->productVariants;
if ($variants->count() > 0) {
    foreach ($variants as $variant) {
        echo "- {$variant->name}: {$variant->sku} (Active: " . ($variant->is_active ? 'Yes' : 'No') . ")\n";
    }
} else {
    echo "- No variants found\n";
}

echo "\nCHECKING FOR DUPLICATE SKUs:\n";
$expectedBWSku = $product->sku . '-BW';
$expectedColorSku = $product->sku . '-Color';

echo "Expected BW SKU: {$expectedBWSku}\n";
echo "Expected Color SKU: {$expectedColorSku}\n";

// Check if these SKUs exist anywhere in the database
$duplicateBW = ProductVariant::where('sku', $expectedBWSku)->get();
$duplicateColor = ProductVariant::where('sku', $expectedColorSku)->get();

if ($duplicateBW->count() > 0) {
    echo "⚠️  DUPLICATE BW SKU FOUND:\n";
    foreach ($duplicateBW as $variant) {
        echo "   - Product ID: {$variant->product_id}, Name: {$variant->name}, SKU: {$variant->sku}\n";
    }
} else {
    echo "✅ BW SKU is available\n";
}

if ($duplicateColor->count() > 0) {
    echo "⚠️  DUPLICATE COLOR SKU FOUND:\n";
    foreach ($duplicateColor as $variant) {
        echo "   - Product ID: {$variant->product_id}, Name: {$variant->name}, SKU: {$variant->sku}\n";
    }
} else {
    echo "✅ Color SKU is available\n";
}

echo "\nCHECKING EXISTING VARIANTS BY NAME:\n";
$existingByName = $product->productVariants()
    ->whereIn('name', ['BW', 'Color'])
    ->get();

if ($existingByName->count() > 0) {
    echo "Found existing BW/Color variants:\n";
    foreach ($existingByName as $variant) {
        echo "- {$variant->name}: {$variant->sku}\n";
    }
} else {
    echo "No existing BW/Color variants found\n";
}

echo "\n=== SOLUTION ===\n";

if ($duplicateBW->count() > 0 || $duplicateColor->count() > 0) {
    echo "The error occurred because variants with these SKUs already exist.\n";
    
    if ($existingByName->count() > 0) {
        echo "✅ Since this product already has BW/Color variants, we should just update the product flags:\n";
        echo "   - Set is_print_service = true\n";
        echo "   - Set is_smart_print_enabled = true\n";
        echo "   - Skip variant creation\n";
    } else {
        echo "🔧 We need to generate unique SKUs or clean up duplicates.\n";
        
        // Check if duplicates belong to the same product
        foreach ($duplicateBW as $variant) {
            if ($variant->product_id == $product->id) {
                echo "   - BW variant belongs to same product - safe to use existing\n";
            } else {
                $otherProduct = Product::find($variant->product_id);
                echo "   - BW variant belongs to different product: " . ($otherProduct ? $otherProduct->name : 'Unknown') . "\n";
            }
        }
        
        foreach ($duplicateColor as $variant) {
            if ($variant->product_id == $product->id) {
                echo "   - Color variant belongs to same product - safe to use existing\n";
            } else {
                $otherProduct = Product::find($variant->product_id);
                echo "   - Color variant belongs to different product: " . ($otherProduct ? $otherProduct->name : 'Unknown') . "\n";
            }
        }
    }
} else {
    echo "✅ No duplicates found. Conversion should work normally.\n";
}