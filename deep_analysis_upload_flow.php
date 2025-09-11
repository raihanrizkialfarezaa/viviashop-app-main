<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;
use App\Models\PrintSession;
use Illuminate\Support\Facades\DB;

echo "🔍 DEEP ANALYSIS OF UPLOAD FLOW\n";
echo "================================\n\n";

echo "📋 Print Sessions (Latest 5):\n";
$sessions = PrintSession::orderBy('created_at', 'desc')->take(5)->get();
foreach ($sessions as $session) {
    echo "ID: {$session->id} | Token: {$session->session_token} | Status: {$session->status} | Created: {$session->created_at}\n";
}

echo "\n📋 Print Orders (Latest 5):\n";
$orders = PrintOrder::orderBy('created_at', 'desc')->take(5)->get();
foreach ($orders as $order) {
    echo "ID: {$order->id} | Code: {$order->order_code} | Session ID: {$order->print_session_id} | Status: {$order->status} | Created: {$order->created_at}\n";
}

echo "\n📋 Print Files (Latest 10):\n";
$files = PrintFile::orderBy('created_at', 'desc')->take(10)->get();
foreach ($files as $file) {
    echo "ID: {$file->id} | Session: {$file->print_session_id} | Order: {$file->print_order_id} | Path: {$file->file_path} | Created: {$file->created_at}\n";
}

echo "\n🎯 Analyzing file-order relationship mismatch...\n";

$latestOrder = PrintOrder::orderBy('created_at', 'desc')->first();
echo "\nLatest Order: {$latestOrder->order_code} (ID: {$latestOrder->id})\n";
echo "Order Session ID: {$latestOrder->print_session_id}\n";

$filesForOrder = PrintFile::where('print_order_id', $latestOrder->id)->get();
echo "Files linked to ORDER: " . $filesForOrder->count() . "\n";

if ($latestOrder->print_session_id) {
    $filesForSession = PrintFile::where('print_session_id', $latestOrder->print_session_id)->get();
    echo "Files linked to SESSION: " . $filesForSession->count() . "\n";
    
    foreach ($filesForSession as $sessionFile) {
        echo "  Session File ID: {$sessionFile->id} | Order ID: {$sessionFile->print_order_id} | Path: {$sessionFile->file_path}\n";
    }
}

echo "\n🔍 Checking file existence in both locations...\n";
$allFiles = PrintFile::where('print_order_id', $latestOrder->id)
    ->orWhere('print_session_id', $latestOrder->print_session_id)
    ->get();

foreach ($allFiles as $file) {
    $storagePath = storage_path('app/' . $file->file_path);
    $publicPath = public_path('storage/' . $file->file_path);
    
    echo "File ID: {$file->id}\n";
    echo "  Path: {$file->file_path}\n";
    echo "  Storage exists: " . (file_exists($storagePath) ? "YES" : "NO") . "\n";
    echo "  Public exists: " . (file_exists($publicPath) ? "YES" : "NO") . "\n";
}

echo "\n🌐 Testing current admin API logic...\n";
echo "Admin Controller Query: PrintOrder::with(['files'])->findOrFail({$latestOrder->id})\n";

$testOrder = PrintOrder::with(['files'])->findOrFail($latestOrder->id);
echo "Files relationship count: " . $testOrder->files->count() . "\n";

if ($testOrder->files->count() === 0) {
    echo "\n❌ PROBLEM FOUND: No files linked to order via relationship\n";
    echo "🔧 Checking if files exist with session_id instead...\n";
    
    if ($testOrder->print_session_id) {
        $sessionFiles = PrintFile::where('print_session_id', $testOrder->print_session_id)->get();
        echo "Files found with session_id: " . $sessionFiles->count() . "\n";
        
        if ($sessionFiles->count() > 0) {
            echo "💡 SOLUTION: Files are linked to session, not order!\n";
            echo "Need to update files to link to order_id after checkout\n";
        }
    }
}

echo "\n✅ Deep analysis complete!\n";
