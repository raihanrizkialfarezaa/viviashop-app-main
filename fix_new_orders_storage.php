<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;

echo "🔧 FIXING FILE STORAGE PATHS FOR NEW ORDERS\n";
echo "=============================================\n\n";

$orders = PrintOrder::whereDate('created_at', '2025-09-11')
    ->orderBy('created_at', 'desc')
    ->take(5)
    ->get();

foreach ($orders as $order) {
    echo "📦 Order: {$order->order_code}\n";
    
    $files = PrintFile::where('print_order_id', $order->id)->get();
    echo "Files in DB: " . $files->count() . "\n";
    
    foreach ($files as $file) {
        $storagePath = storage_path('app/' . $file->file_path);
        $publicPath = public_path('storage/' . $file->file_path);
        
        echo "  📁 File: {$file->file_path}\n";
        echo "     Storage exists: " . (file_exists($storagePath) ? "YES" : "NO") . "\n";
        echo "     Public exists: " . (file_exists($publicPath) ? "YES" : "NO") . "\n";
        
        if (!file_exists($storagePath) && file_exists($publicPath)) {
            echo "     🔧 Copying from public to storage...\n";
            
            $dir = dirname($storagePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            if (copy($publicPath, $storagePath)) {
                echo "     ✅ File copied successfully\n";
            } else {
                echo "     ❌ Failed to copy file\n";
            }
        }
    }
    
    echo "\n🧪 Testing API for order {$order->id}...\n";
    try {
        $controller = new App\Http\Controllers\Admin\PrintServiceController(app('App\Services\PrintService'));
        $request = new Illuminate\Http\Request();
        $response = $controller->printFiles($request, $order->id);
        $data = json_decode($response->getContent(), true);
        
        if (isset($data['success']) && $data['success']) {
            echo "✅ API SUCCESS - Files: " . count($data['files']) . "\n";
            foreach ($data['files'] as $file) {
                echo "  - {$file['name']}\n";
            }
        } else {
            echo "❌ API FAILED: " . ($data['error'] ?? 'Unknown') . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n\n";
}

echo "✅ All recent orders processed!\n";
