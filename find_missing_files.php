<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;
use Illuminate\Support\Facades\Storage;

echo "🔍 FINDING MISSING FILES\n";
echo "========================\n\n";

$latestOrder = PrintOrder::orderBy('created_at', 'desc')->first();
echo "Latest Order: {$latestOrder->order_code}\n\n";

$file = PrintFile::where('print_order_id', $latestOrder->id)->first();
echo "File DB Path: {$file->file_path}\n";

$storageAppPath = storage_path('app');
echo "Storage App Path: {$storageAppPath}\n";

echo "\n🔍 Searching for actual file...\n";

$searchDirs = [
    storage_path('app'),
    storage_path('app/print-files'),
    storage_path('app/public'),
    storage_path('app/uploads'),
    public_path('uploads'),
    public_path('print-files')
];

foreach ($searchDirs as $dir) {
    echo "Checking: {$dir}\n";
    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && strpos($file->getFilename(), '1757576599') !== false) {
                echo "  ✅ FOUND: {$file->getPathname()}\n";
                
                $correctPath = str_replace($storageAppPath . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $correctPath = str_replace('\\', '/', $correctPath);
                
                echo "  Correct DB Path: {$correctPath}\n";
                
                $printFile = PrintFile::where('print_order_id', $latestOrder->id)->first();
                $printFile->update(['file_path' => $correctPath]);
                echo "  ✅ Updated database path\n";
                
                break 2;
            }
        }
    }
}

echo "\n✅ File search complete!\n";
