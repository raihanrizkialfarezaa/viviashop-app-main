<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING SHOP DETAIL PAGES ===\n";

// Test product 3 (simple with variants)
echo "\n--- Testing Product 3 (Simple with Variants) ---\n";
$response3 = file_get_contents('http://127.0.0.1:8000/shop/detail/3');
if ($response3) {
    echo "✓ Product 3 page loads successfully\n";
    if (strpos($response3, 'Pilih varian terlebih dahulu') !== false) {
        echo "✗ Still showing variant selection for simple product\n";
    } else {
        echo "✓ Not showing variant selection for simple product\n";
    }
    if (strpos($response3, 'Tambah ke Keranjang') !== false) {
        echo "✓ Shows 'Tambah ke Keranjang' button\n";
    } else {
        echo "✗ Not showing 'Tambah ke Keranjang' button\n";
    }
} else {
    echo "✗ Product 3 page failed to load\n";
}

// Test product 4 (simple without variants)
echo "\n--- Testing Product 4 (Simple without Variants) ---\n";
$response4 = file_get_contents('http://127.0.0.1:8000/shop/detail/4');
if ($response4) {
    echo "✓ Product 4 page loads successfully\n";
    if (strpos($response4, 'Pilih varian terlebih dahulu') !== false) {
        echo "✗ Still showing variant selection for simple product\n";
    } else {
        echo "✓ Not showing variant selection for simple product\n";
    }
    if (strpos($response4, 'Tambah ke Keranjang') !== false) {
        echo "✓ Shows 'Tambah ke Keranjang' button\n";
    } else {
        echo "✗ Not showing 'Tambah ke Keranjang' button\n";
    }
} else {
    echo "✗ Product 4 page failed to load\n";
}

echo "\nTest completed.\n";
