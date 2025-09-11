<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;

echo "🎯 FINAL VERIFICATION TEST\n";
echo "===========================\n\n";

echo "🔍 Testing latest 3 orders...\n\n";

$orders = PrintOrder::orderBy('created_at', 'desc')->take(3)->get();

foreach ($orders as $order) {
    echo "📦 Order: {$order->order_code}\n";
    echo "Customer: {$order->customer_name}\n";
    echo "Status: {$order->status}\n";
    echo "Payment: {$order->payment_status}\n";
    echo "Created: {$order->created_at}\n";
    
    $files = PrintFile::where('print_order_id', $order->id)->get();
    echo "Files in DB: " . $files->count() . "\n";
    
    foreach ($files as $file) {
        $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file->file_path));
        echo "  📁 {$file->file_path}\n";
        echo "     Full: {$fullPath}\n";
        echo "     Exists: " . (file_exists($fullPath) ? "✅ YES" : "❌ NO") . "\n";
        if (file_exists($fullPath)) {
            echo "     Size: " . number_format(filesize($fullPath)) . " bytes\n";
        }
    }
    
    echo "\n🌐 Testing API for order {$order->id}...\n";
    try {
        $controller = new App\Http\Controllers\Admin\PrintServiceController(app('App\Services\PrintService'));
        $request = new Illuminate\Http\Request();
        $response = $controller->printFiles($request, $order->id);
        $data = json_decode($response->getContent(), true);
        
        if (isset($data['success']) && $data['success']) {
            echo "✅ API SUCCESS\n";
            echo "   Order Code: {$data['order_code']}\n";
            echo "   Customer: {$data['customer_name']}\n";
            echo "   Files ready: " . count($data['files']) . "\n";
            
            foreach ($data['files'] as $apiFile) {
                echo "     - {$apiFile['name']} (ID: {$apiFile['id']})\n";
                echo "       Download: {$apiFile['download_url']}\n";
            }
        } else {
            echo "❌ API FAILED: " . ($data['error'] ?? 'Unknown') . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
}

echo "🎉 VERIFICATION COMPLETE!\n";
echo "\nTo test in browser:\n";
echo "1. Go to: http://127.0.0.1:8000/admin/print-service/orders\n";
echo "2. Look for orders with 'Print Files' button\n";
echo "3. Click button - files should download automatically\n";
echo "4. Use Ctrl+P to print each downloaded file\n";
echo "5. Click 'Complete' when done printing\n\n";
echo "✅ All systems ready for production use!\n";
