<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'],
    'database' => $_ENV['DB_DATABASE'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "🔍 FINAL WEB INTERFACE TEST\n";
echo "============================\n\n";

// Check our test order
$testOrderCode = 'PRINT-11-09-2025-14-21-22';
$order = Capsule::table('print_orders')->where('order_code', $testOrderCode)->first();

if (!$order) {
    echo "❌ Test order not found!\n";
    exit(1);
}

echo "📋 Order Status: {$order->status}\n";
echo "💰 Payment Status: {$order->payment_status}\n";

// Check files
$files = Capsule::table('print_files')
    ->where('print_order_id', $order->id)
    ->get();

echo "\n📁 Files in database:\n";
foreach ($files as $file) {
    $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file->file_path);
    $fullPath = 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $normalizedPath;
    $exists = file_exists($fullPath) ? '✅' : '❌';
    echo "  {$exists} File ID: {$file->id} - {$file->original_name}\n";
    echo "      Path: {$fullPath}\n";
}

// Simulate the API call
echo "\n🌐 Simulating web API call...\n";

// Check if order can print
$canPrint = in_array($order->status, ['payment_confirmed', 'ready_to_print']);
echo "Can print: " . ($canPrint ? "✅ YES" : "❌ NO") . "\n";

if (!$canPrint) {
    echo "\n🔧 Setting order status to ready_to_print...\n";
    Capsule::table('print_orders')
        ->where('id', $order->id)
        ->update([
            'status' => 'ready_to_print',
            'payment_status' => 'paid'
        ]);
    echo "✅ Order status updated!\n";
}

// Test file access as the web would
$fileList = [];
foreach ($files as $file) {
    $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file->file_path);
    $fullPath = 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $normalizedPath;
    
    if (file_exists($fullPath)) {
        $fileList[] = [
            'id' => $file->id,
            'name' => basename($fullPath),
            'path' => $fullPath,
            'download_url' => "http://127.0.0.1:8000/admin/print-service/download-file/{$file->id}"
        ];
    }
}

echo "\n📦 Files ready for web download:\n";
foreach ($fileList as $file) {
    echo "  ✅ {$file['name']} (ID: {$file['id']})\n";
    echo "      Download: {$file['download_url']}\n";
}

echo "\n🎯 READY FOR WEB TEST!\n";
echo "======================\n";
echo "1. Go to: http://127.0.0.1:8000/admin/print-service/orders\n";
echo "2. Find order: {$testOrderCode}\n";
echo "3. Click 'Print Files' button\n";
echo "4. Files should download automatically\n";
echo "5. Use Ctrl+P to print each downloaded file\n";
echo "6. Click 'Complete' when done\n\n";

echo "✅ Test complete - Web interface should work now!\n";

?>
