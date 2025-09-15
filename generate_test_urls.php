<?php

require_once 'vendor/autoload.php';

// Laravel bootstrap
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Manual Callback URL Test ===\n\n";

// Generate test URLs
$baseUrl = config('app.url');
$testOrderCode = 'PRINT-TEST-' . date('dmY-His');

echo "Test URLs untuk manual testing:\n\n";

echo "1. Payment Finish (Success):\n";
echo "{$baseUrl}/print-service/payment/finish?order_id={$testOrderCode}&status_code=200&transaction_status=settlement\n\n";

echo "2. Payment Unfinish:\n";
echo "{$baseUrl}/print-service/payment/unfinish?order_id={$testOrderCode}\n\n";

echo "3. Payment Error:\n";
echo "{$baseUrl}/print-service/payment/error?order_id={$testOrderCode}\n\n";

echo "📝 Test Instructions:\n";
echo "1. Copy dan paste URL di atas ke browser\n";
echo "2. URL akan redirect sesuai dengan logic callback\n";
echo "3. Jika order_id tidak ditemukan, akan redirect ke /print-service\n";
echo "4. Jika order_id ditemukan, akan redirect ke session token page\n\n";

echo "🎯 Expected Behavior:\n";
echo "✓ Tidak lagi redirect ke example.com\n";
echo "✓ Redirect ke halaman aplikasi yang benar\n";
echo "✓ Menampilkan pesan status pembayaran\n";
echo "✓ User tetap dalam flow aplikasi\n\n";

echo "=== Test URLs Generated ===\n";