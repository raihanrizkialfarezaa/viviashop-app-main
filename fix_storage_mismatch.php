<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;
use Illuminate\Support\Facades\File;

echo "🔧 FIXING FILE STORAGE MISMATCH\n";
echo "================================\n\n";

$latestOrder = PrintOrder::orderBy('created_at', 'desc')->first();
echo "Order: {$latestOrder->order_code}\n";

$file = PrintFile::where('print_order_id', $latestOrder->id)->first();
echo "DB Path: {$file->file_path}\n";

$dbPath = storage_path('app/' . $file->file_path);
$publicPath = public_path('storage/' . $file->file_path);

echo "Storage path: {$dbPath}\n";
echo "Public path: {$publicPath}\n";

echo "Storage exists: " . (file_exists($dbPath) ? "YES" : "NO") . "\n";
echo "Public exists: " . (file_exists($publicPath) ? "YES" : "NO") . "\n";

if (file_exists($publicPath) && !file_exists($dbPath)) {
    echo "\n🔧 Moving file from public to storage...\n";
    
    $dir = dirname($dbPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: {$dir}\n";
    }
    
    if (copy($publicPath, $dbPath)) {
        echo "✅ File copied successfully\n";
        echo "Size: " . filesize($dbPath) . " bytes\n";
    } else {
        echo "❌ Failed to copy file\n";
    }
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
        }
    } else {
        echo "❌ API FAILED: " . ($data['error'] ?? 'Unknown') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n✅ Fix complete!\n";
