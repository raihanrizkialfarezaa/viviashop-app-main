<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductVariant;

echo "Checking Kertas Padang variants by SKU...\n\n";

$variants = ProductVariant::whereIn('sku', ['kfhks2424725', 'duiwru9479759'])->with('product')->get();

foreach($variants as $variant) {
    echo "Variant: " . $variant->name . " (SKU: " . $variant->sku . ")\n";
    echo "  Product: " . $variant->product->name . "\n";
    echo "  Paper Size: " . ($variant->paper_size ?? 'NULL') . "\n";
    echo "  Print Type: " . ($variant->print_type ?? 'NULL') . "\n";
    echo "  Stock: " . $variant->stock . "\n";
    echo "  Price: " . $variant->price . "\n\n";
}

echo "Available enum values:\n";
echo "paper_size: A4, A3, F4\n";
echo "print_type: bw, color\n\n";

echo "Updating variants with correct enum values...\n\n";

// Update Padang Black & White
$bwVariant = ProductVariant::where('sku', 'kfhks2424725')->first();
if($bwVariant) {
    $bwVariant->paper_size = 'A4';  // Assuming A4 size
    $bwVariant->print_type = 'bw';  // Black & White
    $bwVariant->save();
    echo "Updated: " . $bwVariant->name . " - Paper Size: A4, Print Type: bw\n";
}

// Update Padang Colorful
$colorVariant = ProductVariant::where('sku', 'duiwru9479759')->first();
if($colorVariant) {
    $colorVariant->paper_size = 'A4';  // Assuming A4 size
    $colorVariant->print_type = 'color';  // Color
    $colorVariant->save();
    echo "Updated: " . $colorVariant->name . " - Paper Size: A4, Print Type: color\n";
}

echo "\nRechecking updated variants...\n\n";

$updatedVariants = ProductVariant::whereIn('sku', ['kfhks2424725', 'duiwru9479759'])->with('product')->get();

foreach($updatedVariants as $variant) {
    echo "Variant: " . $variant->name . " (SKU: " . $variant->sku . ")\n";
    echo "  Paper Size: " . ($variant->paper_size ?? 'NULL') . "\n";
    echo "  Print Type: " . ($variant->print_type ?? 'NULL') . "\n";
    echo "  Stock: " . $variant->stock . "\n\n";
}