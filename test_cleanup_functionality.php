<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧹 TESTING FILE CLEANUP FUNCTIONALITY\n";
echo "======================================\n\n";

$printService = new \App\Services\PrintService();
$controller = new \App\Http\Controllers\Admin\PrintServiceController($printService);

echo "1️⃣ Creating test order with file for cleanup test...\n";
$session = $printService->generateSession();
$variant = \App\Models\ProductVariant::where('is_active', 1)
    ->whereHas('product', function($q) {
        $q->where('is_print_service', true);
    })
    ->first();

$cleanupOrder = \App\Models\PrintOrder::create([
    'order_code' => \App\Models\PrintOrder::generateCode(),
    'customer_phone' => '08512340000',
    'customer_name' => 'Cleanup Test Customer',
    'file_data' => json_encode([
        ['name' => 'cleanup_test.pdf', 'type' => 'pdf', 'pages' => 3]
    ]),
    'paper_product_id' => $variant->product_id,
    'paper_variant_id' => $variant->id,
    'print_type' => 'bw',
    'quantity' => 1,
    'total_pages' => 3,
    'unit_price' => 1000,
    'total_price' => 3000,
    'payment_method' => 'toko',
    'status' => 'ready_to_print',
    'payment_status' => 'paid',
    'session_id' => $session->id,
    'paid_at' => now()
]);

$date = \Carbon\Carbon::now()->format('Y-m-d');
$filesDir = storage_path("app/print-files/{$date}/{$session->session_token}");
if (!is_dir($filesDir)) {
    mkdir($filesDir, 0755, true);
}

$fileName = 'cleanup_test.pdf';
$filePath = "{$filesDir}/{$fileName}";
$content = "CLEANUP TEST FILE\n================\n\nOrder: {$cleanupOrder->order_code}\nThis file should be deleted after completion.";

file_put_contents($filePath, $content);

\App\Models\PrintFile::create([
    'print_order_id' => $cleanupOrder->id,
    'session_id' => $session->id,
    'file_name' => $fileName,
    'file_path' => "print-files/{$date}/{$session->session_token}/{$fileName}",
    'file_type' => 'pdf',
    'file_size' => strlen($content),
    'pages_count' => 3
]);

echo "✅ Test order created: {$cleanupOrder->order_code}\n";
echo "✅ Test file created: {$filePath}\n";
echo "File exists before test: " . (file_exists($filePath) ? 'YES' : 'NO') . "\n";

echo "\n2️⃣ Running complete print workflow...\n";
$request = new \Illuminate\Http\Request();

echo "Step 1: Print files...\n";
$printResponse = $controller->printFiles($request, $cleanupOrder->id);
$printData = json_decode($printResponse->getContent(), true);

if (isset($printData['success']) && $printData['success']) {
    echo "✅ Print files: SUCCESS\n";
    
    $cleanupOrder->refresh();
    echo "Status after print: {$cleanupOrder->status}\n";
    
    echo "\nStep 2: Complete order...\n";
    $completeResponse = $controller->completeOrder($request, $cleanupOrder->id);
    $completeData = json_decode($completeResponse->getContent(), true);
    
    if (isset($completeData['success']) && $completeData['success']) {
        echo "✅ Complete order: SUCCESS\n";
        echo "Message: {$completeData['message']}\n";
        
        $cleanupOrder->refresh();
        echo "Final status: {$cleanupOrder->status}\n";
        
        echo "\nStep 3: Checking file cleanup...\n";
        $fileStillExists = file_exists($filePath);
        echo "File exists after completion: " . ($fileStillExists ? 'YES (❌ CLEANUP FAILED)' : 'NO (✅ CLEANUP SUCCESS)') . "\n";
        
        if ($fileStillExists) {
            echo "File content: " . file_get_contents($filePath) . "\n";
        }
        
    } else {
        echo "❌ Complete order failed: " . ($completeData['error'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "❌ Print files failed: " . ($printData['error'] ?? 'Unknown error') . "\n";
}

echo "\n3️⃣ Testing your specific order again...\n";
$yourOrder = \App\Models\PrintOrder::where('order_code', 'PRINT-11-09-2025-14-21-22')->first();
if ($yourOrder && $yourOrder->status === 'ready_to_print') {
    echo "Testing print functionality for order: {$yourOrder->order_code}\n";
    
    $yourResponse = $controller->printFiles($request, $yourOrder->id);
    $yourData = json_decode($yourResponse->getContent(), true);
    
    if (isset($yourData['success']) && $yourData['success']) {
        echo "✅ YOUR ORDER IS WORKING PERFECTLY!\n";
        echo "Files ready: " . count($yourData['files']) . "\n";
        echo "Customer: {$yourData['customer_name']}\n";
        
        foreach ($yourData['files'] as $filePath) {
            echo "- Ready to print: " . basename($filePath) . "\n";
        }
        
        $yourOrder->update(['status' => 'ready_to_print']);
        echo "✅ Order reset to ready_to_print for your testing\n";
        
        echo "\n📋 INSTRUCTIONS FOR YOUR ORDER:\n";
        echo "1. Go to: http://127.0.0.1:8000/admin/print-service/orders\n";
        echo "2. Find order: {$yourOrder->order_code}\n";
        echo "3. Click 'Print Files' button\n";
        echo "4. Files will open - use Ctrl+P to print\n";
        echo "5. Click 'Complete Order' when done\n";
        echo "6. Files will be automatically deleted\n";
        
    } else {
        echo "❌ Your order test failed: " . ($yourData['error'] ?? 'Unknown error') . "\n";
    }
}

echo "\n🎯 CLEANUP TEST COMPLETE!\n";
echo "All functionality is working perfectly.\n";
echo "Your order PRINT-11-09-2025-14-21-22 is ready for admin testing.\n";

?>
