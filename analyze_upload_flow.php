<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;
use App\Models\PrintSession;
use Illuminate\Support\Facades\DB;

echo "🔍 ANALYZING NEW UPLOAD FLOW\n";
echo "=============================\n\n";

echo "📋 Latest Print Orders (All):\n";
$orders = PrintOrder::orderBy('created_at', 'desc')->take(5)->get();
foreach ($orders as $order) {
    echo "Order ID: {$order->id} | Code: {$order->order_code} | Status: {$order->status} | Payment: {$order->payment_status} | Created: {$order->created_at}\n";
}

echo "\n📋 Latest Print Sessions:\n";
$sessions = PrintSession::orderBy('created_at', 'desc')->take(5)->get();
foreach ($sessions as $session) {
    echo "Session ID: {$session->id} | Code: {$session->session_code} | Status: {$session->status} | Created: {$session->created_at}\n";
}

echo "\n📋 Latest Print Files:\n";
$files = PrintFile::orderBy('created_at', 'desc')->take(5)->get();
foreach ($files as $file) {
    echo "File ID: {$file->id} | Session: {$file->print_session_id} | Order: {$file->print_order_id} | Path: {$file->file_path} | Created: {$file->created_at}\n";
}

echo "\n🎯 Latest Order Analysis:\n";
$latestOrder = PrintOrder::orderBy('created_at', 'desc')->first();
if ($latestOrder) {
    echo "Order: {$latestOrder->order_code}\n";
    echo "Customer: {$latestOrder->customer_name}\n";
    echo "Status: {$latestOrder->status}\n";
    echo "Payment: {$latestOrder->payment_status}\n";
    echo "Created: {$latestOrder->created_at}\n";
    
    $orderFiles = PrintFile::where('print_order_id', $latestOrder->id)->get();
    echo "Files in order: " . $orderFiles->count() . "\n";
    
    foreach ($orderFiles as $file) {
        echo "  File: {$file->file_path}\n";
        $fullPath = storage_path('app/' . $file->file_path);
        echo "  Full path: {$fullPath}\n";
        echo "  Exists: " . (file_exists($fullPath) ? "YES" : "NO") . "\n";
        
        if (!file_exists($fullPath)) {
            echo "  🔍 Searching storage for similar files...\n";
            $baseName = basename($file->file_path);
            
            $searchPaths = [
                storage_path('app'),
                storage_path('app/print-files'),
                storage_path('app/public'),
                public_path('storage')
            ];
            
            foreach ($searchPaths as $searchPath) {
                if (is_dir($searchPath)) {
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($searchPath, RecursiveDirectoryIterator::SKIP_DOTS)
                    );
                    
                    foreach ($iterator as $foundFile) {
                        if ($foundFile->isFile() && stripos($foundFile->getFilename(), substr($baseName, 0, 10)) !== false) {
                            echo "    Found similar: {$foundFile->getPathname()}\n";
                        }
                    }
                }
            }
        }
    }
    
    echo "\n🧪 Testing upload relationship chain:\n";
    if ($latestOrder->print_session_id) {
        $session = PrintSession::find($latestOrder->print_session_id);
        if ($session) {
            echo "  Session found: {$session->session_code}\n";
            $sessionFiles = PrintFile::where('print_session_id', $session->id)->get();
            echo "  Files in session: " . $sessionFiles->count() . "\n";
            
            foreach ($sessionFiles as $sFile) {
                echo "    Session file: {$sFile->file_path}\n";
                $sFullPath = storage_path('app/' . $sFile->file_path);
                echo "    Exists: " . (file_exists($sFullPath) ? "YES" : "NO") . "\n";
            }
        }
    }
}

echo "\n🔍 Storage Directory Analysis:\n";
$printFilesDir = storage_path('app/print-files');
if (is_dir($printFilesDir)) {
    echo "Print files directory exists\n";
    
    $todayDir = storage_path('app/print-files/2025-09-11');
    if (is_dir($todayDir)) {
        echo "Today's directory exists\n";
        $subdirs = array_filter(glob($todayDir . '/*'), 'is_dir');
        echo "Subdirectories today: " . count($subdirs) . "\n";
        
        foreach (array_slice($subdirs, -3) as $subdir) {
            echo "  Recent dir: " . basename($subdir) . "\n";
            $filesInDir = glob($subdir . '/*');
            foreach ($filesInDir as $fileInDir) {
                if (is_file($fileInDir)) {
                    echo "    File: " . basename($fileInDir) . " (" . filesize($fileInDir) . " bytes)\n";
                }
            }
        }
    }
}

echo "\n✅ Analysis complete!\n";
