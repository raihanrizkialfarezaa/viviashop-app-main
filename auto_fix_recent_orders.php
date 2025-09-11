<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;

echo "🔄 AUTO-FIX ALL RECENT ORDERS\n";
echo "==============================\n\n";

$orders = PrintOrder::whereDate('created_at', '2025-09-11')
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get();

echo "Processing " . $orders->count() . " recent orders...\n\n";

foreach ($orders as $order) {
    echo "📦 Order: {$order->order_code}\n";
    
    $files = PrintFile::where('print_order_id', $order->id)->get();
    $fixedCount = 0;
    
    foreach ($files as $file) {
        $storagePath = storage_path('app/' . $file->file_path);
        $publicPath = public_path('storage/' . $file->file_path);
        
        if (!file_exists($storagePath)) {
            if (file_exists($publicPath)) {
                $dir = dirname($storagePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                if (copy($publicPath, $storagePath)) {
                    $fixedCount++;
                    echo "  ✅ Fixed: " . basename($file->file_path) . "\n";
                }
            } else {
                $baseName = basename($file->file_path);
                $searchPaths = [
                    public_path('storage'),
                    storage_path('app/print-files')
                ];
                
                foreach ($searchPaths as $searchPath) {
                    if (is_dir($searchPath)) {
                        $iterator = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($searchPath, RecursiveDirectoryIterator::SKIP_DOTS)
                        );
                        
                        foreach ($iterator as $foundFile) {
                            if ($foundFile->isFile() && $foundFile->getFilename() === $baseName) {
                                $dir = dirname($storagePath);
                                if (!is_dir($dir)) {
                                    mkdir($dir, 0755, true);
                                }
                                
                                if (copy($foundFile->getPathname(), $storagePath)) {
                                    $fixedCount++;
                                    echo "  ✅ Found & fixed: " . basename($file->file_path) . "\n";
                                }
                                break 2;
                            }
                        }
                    }
                }
            }
        }
    }
    
    echo "  Files fixed: {$fixedCount}/{$files->count()}\n";
    echo "  -------------------------\n";
}

echo "\n🧪 Testing latest 3 orders...\n";
$testOrders = PrintOrder::orderBy('created_at', 'desc')->take(3)->get();

foreach ($testOrders as $order) {
    echo "\n📋 Testing: {$order->order_code}\n";
    try {
        $controller = new App\Http\Controllers\Admin\PrintServiceController(app('App\Services\PrintService'));
        $request = new Illuminate\Http\Request();
        $response = $controller->printFiles($request, $order->id);
        $data = json_decode($response->getContent(), true);
        
        if (isset($data['success']) && $data['success']) {
            echo "✅ SUCCESS - Files: " . count($data['files']) . "\n";
        } else {
            echo "❌ FAILED: " . ($data['error'] ?? 'Unknown') . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ All orders processed and ready!\n";
echo "\nTo test in browser:\n";
echo "1. Go to: http://127.0.0.1:8000/admin/print-service/orders\n";
echo "2. Click 'See Files' button on any order\n";
echo "3. Files will open in new tabs for viewing\n";
echo "4. Use Ctrl+P to print each file\n";
echo "5. Admin can manually control printing process\n\n";
