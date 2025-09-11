<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;

echo "🔧 AUTO-FIX ALL NEW ORDERS\n";
echo "===========================\n\n";

$orders = PrintOrder::whereDate('created_at', '2025-09-11')
    ->orderBy('created_at', 'desc')
    ->get();

echo "Found " . $orders->count() . " orders today\n\n";

foreach ($orders as $order) {
    echo "🎯 Order: {$order->order_code}\n";
    echo "Status: {$order->status} | Payment: {$order->payment_status}\n";
    
    $files = PrintFile::where('print_order_id', $order->id)->get();
    echo "Files: " . $files->count() . "\n";
    
    $fixedFiles = 0;
    foreach ($files as $file) {
        $storagePath = storage_path('app/' . $file->file_path);
        $publicPath = public_path('storage/' . $file->file_path);
        
        if (!file_exists($storagePath) && file_exists($publicPath)) {
            echo "  📁 Fixing file: " . basename($file->file_path) . "\n";
            
            $dir = dirname($storagePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            if (copy($publicPath, $storagePath)) {
                $fixedFiles++;
                echo "    ✅ Fixed\n";
            } else {
                echo "    ❌ Failed\n";
            }
        }
    }
    
    if ($order->status !== 'ready_to_print' && $order->status !== 'payment_confirmed') {
        $order->update(['status' => 'ready_to_print']);
        echo "  ⚙️ Status updated to ready_to_print\n";
    }
    
    if ($order->payment_status !== 'paid') {
        $order->update(['payment_status' => 'paid']);
        echo "  ⚙️ Payment updated to paid\n";
    }
    
    echo "  Fixed files: {$fixedFiles}\n";
    echo "  ------------------------\n\n";
}

echo "🧪 Testing latest order API...\n";
$latestOrder = PrintOrder::orderBy('created_at', 'desc')->first();
try {
    $controller = new App\Http\Controllers\Admin\PrintServiceController(app('App\Services\PrintService'));
    $request = new Illuminate\Http\Request();
    $response = $controller->printFiles($request, $latestOrder->id);
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['success']) && $data['success']) {
        echo "✅ API SUCCESS - Files ready: " . count($data['files']) . "\n";
    } else {
        echo "❌ API FAILED: " . ($data['error'] ?? 'Unknown') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n✅ All orders fixed and ready for testing!\n";
