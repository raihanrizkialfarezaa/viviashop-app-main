<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;

echo "🔧 FIXING LATEST ORDER FILE\n";
echo "============================\n\n";

$latestOrder = PrintOrder::orderBy('created_at', 'desc')->first();
echo "Order: {$latestOrder->order_code}\n";

$file = PrintFile::where('print_order_id', $latestOrder->id)->first();
if (!$file) {
    echo "❌ No files found in database\n";
    exit;
}

echo "DB File: {$file->file_path}\n";

$storagePath = storage_path('app/' . $file->file_path);
$publicPath = public_path('storage/' . $file->file_path);

echo "Storage path: {$storagePath}\n";
echo "Public path: {$publicPath}\n";

echo "Storage exists: " . (file_exists($storagePath) ? "YES" : "NO") . "\n";
echo "Public exists: " . (file_exists($publicPath) ? "YES" : "NO") . "\n";

if (file_exists($publicPath) && !file_exists($storagePath)) {
    echo "\n🔧 Moving file from public to storage...\n";
    
    $dir = dirname($storagePath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Created directory: {$dir}\n";
    }
    
    if (copy($publicPath, $storagePath)) {
        echo "✅ File copied successfully\n";
        echo "Size: " . number_format(filesize($storagePath)) . " bytes\n";
    } else {
        echo "❌ Failed to copy file\n";
    }
} elseif (!file_exists($storagePath) && !file_exists($publicPath)) {
    echo "\n🔍 Searching for file...\n";
    
    $baseName = basename($file->file_path);
    $searchPaths = [
        public_path('storage'),
        storage_path('app'),
        storage_path('app/print-files')
    ];
    
    foreach ($searchPaths as $searchPath) {
        if (is_dir($searchPath)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($searchPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            foreach ($iterator as $foundFile) {
                if ($foundFile->isFile() && $foundFile->getFilename() === $baseName) {
                    echo "Found file: {$foundFile->getPathname()}\n";
                    
                    $dir = dirname($storagePath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    
                    if (copy($foundFile->getPathname(), $storagePath)) {
                        echo "✅ File copied successfully\n";
                        echo "Size: " . number_format(filesize($storagePath)) . " bytes\n";
                    }
                    break 2;
                }
            }
        }
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
            echo "    View: {$file['view_url']}\n";
        }
    } else {
        echo "❌ API FAILED: " . ($data['error'] ?? 'Unknown') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n✅ Fix complete!\n";
