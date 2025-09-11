<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\DB;

echo "=== MENGATUR PRODUCT UNTUK PRINT SERVICE ===\n\n";

$product = Product::find(135);

if (!$product) {
    echo "❌ PRODUCT ID 135 TIDAK DITEMUKAN\n";
    exit;
}

echo "✅ PRODUCT DITEMUKAN:\n";
echo "- ID: {$product->id}\n";
echo "- Name: {$product->name}\n";
echo "- Status: {$product->status}\n";
echo "- is_print_service: " . ($product->is_print_service ? 'YES' : 'NO') . "\n\n";

echo "MENGUPDATE PRODUCT MENJADI PRINT SERVICE...\n";
$product->update(['is_print_service' => true]);

echo "✅ PRODUCT BERHASIL DIUPDATE\n";
echo "- is_print_service: " . ($product->fresh()->is_print_service ? 'YES' : 'NO') . "\n\n";

echo "=== VALIDASI VARIANT UNTUK PRINT SERVICE ===\n";
$variants = $product->productVariants()->get();

foreach ($variants as $variant) {
    echo "- Variant ID: {$variant->id}\n";
    echo "  Size: {$variant->paper_size}\n";
    echo "  Type: {$variant->print_type}\n";
    echo "  Stock: {$variant->stock}\n";
    echo "  Price: {$variant->price}\n\n";
}
