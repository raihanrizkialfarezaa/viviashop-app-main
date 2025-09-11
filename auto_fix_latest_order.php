<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;

echo "🔧 AUTO-FIX FILE STORAGE ISSUE\n";
echo "===============================\n\n";

$latestOrder = PrintOrder::orderBy('created_at', 'desc')->first();
echo "Order: {$latestOrder->order_code}\n";

$files = PrintFile::where('print_order_id', $latestOrder->id)->get();
echo "Files to fix: " . $files->count() . "\n\n";

foreach ($files as $file) {
    echo "Processing File ID: {$file->id}\n";
    echo "Path: {$file->file_path}\n";
    
    $storagePath = storage_path('app/' . $file->file_path);
    $publicPath = public_path('storage/' . $file->file_path);
    
    echo "Storage path: {$storagePath}\n";
    echo "Public path: {$publicPath}\n";
    
    echo "Storage exists: " . (file_exists($storagePath) ? "YES" : "NO") . "\n";
    echo "Public exists: " . (file_exists($publicPath) ? "YES" : "NO") . "\n";
    
    if (!file_exists($storagePath) && file_exists($publicPath)) {
        echo "🔧 Fixing: Copying from public to storage...\n";
        
        $dir = dirname($storagePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "Created directory: {$dir}\n";
        }
        
        if (copy($publicPath, $storagePath)) {
            echo "✅ File copied successfully\n";
            echo "New size: " . filesize($storagePath) . " bytes\n";
        } else {
            echo "❌ Failed to copy file\n";
        }
    } elseif (file_exists($storagePath)) {
        echo "✅ File already exists in storage\n";
    } else {
        echo "❌ File not found in both locations\n";
    }
    
    echo "-------------------\n";
}

echo "\n🧪 Testing API after fix...\n";
try {
    $controller = new App\Http\Controllers\Admin\PrintServiceController(app('App\Services\PrintService'));
    $request = new Illuminate\Http\Request();
    $response = $controller->printFiles($request, $latestOrder->id);
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['success']) && $data['success']) {
        echo "✅ API SUCCESS\n";
        echo "Files ready: " . count($data['files']) . "\n";
        foreach ($data['files'] as $file) {
            echo "  - {$file['name']}\n";
            echo "    View URL: {$file['view_url']}\n";
        }
    } else {
        echo "❌ API FAILED: " . ($data['error'] ?? 'Unknown') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n✅ Fix complete!\n";
