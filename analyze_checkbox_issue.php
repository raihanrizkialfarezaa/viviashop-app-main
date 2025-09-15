<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

echo "=== ANALISIS AKAR MASALAH CHECKBOX ===\n\n";

// Cek log atau trace request terakhir
echo "Analisis produk kertas glossy yang baru dibuat:\n\n";

$glossyProduct = Product::where('name', 'LIKE', '%glossy%')
                       ->orWhere('name', 'LIKE', '%Glossy%')
                       ->orderBy('id', 'desc')
                       ->first();

echo "Produk: {$glossyProduct->name}\n";
echo "Created: {$glossyProduct->created_at}\n";
echo "is_print_service: " . (int)$glossyProduct->is_print_service . "\n";
echo "is_smart_print_enabled: " . (int)$glossyProduct->is_smart_print_enabled . "\n\n";

// Simulasi request yang sama
echo "=== SIMULASI REQUEST FORM ===\n";

// Test langsung dengan request simulation
$testData = [
    'name' => 'Test Debug Checkbox',
    'sku' => 'test-debug-' . time(),
    'type' => 'simple',
    'status' => 1,
    'price' => 5000,
    'weight' => 0.1,
    'brand_id' => 1,
    'category_id' => [3],
    'is_print_service' => 'on',  // Form checkbox biasanya kirim 'on'
    'is_smart_print_enabled' => 'on'
];

echo "Test data yang dikirim:\n";
foreach ($testData as $key => $value) {
    echo "- {$key}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
}
echo "\n";

// Test request->has() logic
$mockRequest = new \Illuminate\Http\Request($testData);

echo "=== TEST REQUEST->HAS() LOGIC ===\n";
echo "request->has('is_print_service'): " . ($mockRequest->has('is_print_service') ? 'true' : 'false') . "\n";
echo "request->has('is_smart_print_enabled'): " . ($mockRequest->has('is_smart_print_enabled') ? 'true' : 'false') . "\n";
echo "request->get('is_print_service'): " . $mockRequest->get('is_print_service', 'null') . "\n";
echo "request->get('is_smart_print_enabled'): " . $mockRequest->get('is_smart_print_enabled', 'null') . "\n\n";

// Test dengan checkbox unchecked
echo "=== TEST CHECKBOX UNCHECKED ===\n";
$testDataUnchecked = array_diff_key($testData, ['is_print_service' => '', 'is_smart_print_enabled' => '']);

$mockRequestUnchecked = new \Illuminate\Http\Request($testDataUnchecked);
echo "request->has('is_print_service') (unchecked): " . ($mockRequestUnchecked->has('is_print_service') ? 'true' : 'false') . "\n";
echo "request->has('is_smart_print_enabled') (unchecked): " . ($mockRequestUnchecked->has('is_smart_print_enabled') ? 'true' : 'false') . "\n\n";

echo "=== KEMUNGKINAN PENYEBAB ===\n";
echo "1. Browser tidak mengirim checkbox values\n";
echo "2. Form method/action salah\n";
echo "3. JavaScript interfering\n";
echo "4. CSRF token issue\n";
echo "5. Validation rules menghapus values\n";
echo "6. Store method tidak menggunakan modified data\n\n";

echo "=== REKOMENDASI DEBUG LANJUTAN ===\n";
echo "1. Periksa Network Tab browser saat submit form\n";
echo "2. Tambahkan logging di ProductController store method\n";
echo "3. Test dengan form sederhana tanpa JavaScript\n";
echo "4. Periksa apakah ada middleware yang modify request\n\n";

echo "Mari kita periksa controller store method sekali lagi...\n";