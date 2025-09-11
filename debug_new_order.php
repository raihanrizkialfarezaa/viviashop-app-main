<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;
use Illuminate\Support\Facades\DB;

echo "🔍 DEBUGGING NEW ORDER\n";
echo "======================\n\n";

echo "📋 Latest Print Orders:\n";
$orders = PrintOrder::orderBy('created_at', 'desc')->take(3)->get();
foreach ($orders as $order) {
    echo "  Order ID: {$order->id}\n";
    echo "  Order Code: {$order->order_code}\n";
    echo "  Customer: {$order->customer_name}\n";
    echo "  Status: {$order->status}\n";
    echo "  Payment: {$order->payment_status}\n";
    echo "  Created: {$order->created_at}\n";
    echo "  Files Count: " . $order->files()->count() . "\n";
    echo "  ---------\n";
}

$latestOrder = PrintOrder::orderBy('created_at', 'desc')->first();
if (!$latestOrder) {
    echo "❌ No orders found\n";
    exit;
}

echo "\n🎯 Latest Order Details:\n";
echo "Order ID: {$latestOrder->id}\n";
echo "Order Code: {$latestOrder->order_code}\n";
echo "Status: {$latestOrder->status}\n";
echo "Payment: {$latestOrder->payment_status}\n";

echo "\n📁 Files for latest order:\n";
$files = PrintFile::where('print_order_id', $latestOrder->id)->get();
echo "Files count in database: " . $files->count() . "\n";

foreach ($files as $file) {
    echo "  File ID: {$file->id}\n";
    echo "  File Path: {$file->file_path}\n";
    echo "  Print Session ID: {$file->print_session_id}\n";
    echo "  Print Order ID: {$file->print_order_id}\n";
    
    $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file->file_path);
    $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . $normalizedPath);
    
    echo "  Normalized Path: {$normalizedPath}\n";
    echo "  Full Path: {$fullPath}\n";
    echo "  File Exists: " . (file_exists($fullPath) ? "✅ YES" : "❌ NO") . "\n";
    
    if (!file_exists($fullPath)) {
        echo "  🔍 Checking alternative paths:\n";
        $alt1 = storage_path('app/print-files/' . basename($file->file_path));
        echo "    Alt1: {$alt1} - " . (file_exists($alt1) ? "✅ YES" : "❌ NO") . "\n";
        
        $alt2 = storage_path('app/' . $file->file_path);
        echo "    Alt2: {$alt2} - " . (file_exists($alt2) ? "✅ YES" : "❌ NO") . "\n";
        
        $searchPattern = storage_path('app/print-files/**/*' . basename($file->file_path));
        $foundFiles = glob($searchPattern);
        if (!empty($foundFiles)) {
            echo "  🔍 Found similar files:\n";
            foreach ($foundFiles as $found) {
                echo "    Found: {$found}\n";
            }
        }
    }
    echo "  ---------\n";
}

echo "\n🔧 Testing canPrint() method:\n";
echo "Can Print: " . ($latestOrder->canPrint() ? "✅ YES" : "❌ NO") . "\n";

if ($latestOrder->status !== 'payment_confirmed' && $latestOrder->status !== 'ready_to_print') {
    echo "\n🔧 Setting order to ready_to_print...\n";
    $latestOrder->update(['status' => 'ready_to_print']);
    echo "✅ Status updated to ready_to_print\n";
}

if ($latestOrder->payment_status !== 'paid') {
    echo "\n🔧 Setting payment to paid...\n";
    $latestOrder->update(['payment_status' => 'paid']);
    echo "✅ Payment status updated to paid\n";
}

echo "\n🌐 Testing API Response:\n";
try {
    $printOrder = PrintOrder::with(['files'])->findOrFail($latestOrder->id);
    
    if (!$printOrder->canPrint()) {
        echo "❌ Order cannot be printed\n";
    } else {
        $files = [];
        foreach ($printOrder->files as $file) {
            $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file->file_path));
            if (file_exists($fullPath)) {
                $files[] = [
                    'id' => $file->id,
                    'name' => basename($fullPath),
                    'path' => $fullPath,
                    'download_url' => "http://127.0.0.1:8000/admin/print-service/download-file/{$file->id}"
                ];
            }
        }

        if (empty($files)) {
            echo "❌ No valid files found for printing\n";
        } else {
            echo "✅ Found " . count($files) . " valid files:\n";
            foreach ($files as $file) {
                echo "  - {$file['name']} (ID: {$file['id']})\n";
                echo "    Download: {$file['download_url']}\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Debug complete!\n";
