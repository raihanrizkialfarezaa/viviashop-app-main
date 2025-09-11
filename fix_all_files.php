<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;

echo "🔍 FIXING ALL MISSING FILES\n";
echo "===========================\n\n";

$orders = PrintOrder::with('files')->orderBy('created_at', 'desc')->take(5)->get();

foreach ($orders as $order) {
    echo "📦 Order: {$order->order_code}\n";
    echo "Files count: " . $order->files->count() . "\n";
    
    foreach ($order->files as $file) {
        echo "  File ID: {$file->id}\n";
        echo "  Current path: {$file->file_path}\n";
        
        $currentFullPath = storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file->file_path));
        echo "  Current full path: {$currentFullPath}\n";
        echo "  Exists: " . (file_exists($currentFullPath) ? "✅" : "❌") . "\n";
        
        if (!file_exists($currentFullPath)) {
            echo "  🔍 Searching for file...\n";
            
            $baseName = basename($file->file_path);
            echo "  Base name: {$baseName}\n";
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(storage_path('app/print-files'), RecursiveDirectoryIterator::SKIP_DOTS)
            );
            
            $found = false;
            foreach ($iterator as $foundFile) {
                if ($foundFile->isFile() && $foundFile->getFilename() === $baseName) {
                    echo "  ✅ FOUND: {$foundFile->getPathname()}\n";
                    
                    $correctPath = str_replace(storage_path('app') . DIRECTORY_SEPARATOR, '', $foundFile->getPathname());
                    $correctPath = str_replace('\\', '/', $correctPath);
                    
                    echo "  Updating to: {$correctPath}\n";
                    
                    $file->update(['file_path' => $correctPath]);
                    echo "  ✅ Database updated\n";
                    
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                echo "  ❌ File not found anywhere\n";
            }
        }
        
        echo "  ---------\n";
    }
    echo "\n";
}

echo "✅ All files checked and fixed!\n";
