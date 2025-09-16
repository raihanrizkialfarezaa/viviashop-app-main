<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;

// Database configuration
$capsule = new DB;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'database'  => 'u875841990_viviashop',
    'username'  => 'root',
    'password'  => '',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== ANALYZING DUPLICATE FILE CREATION PATTERNS ===\n\n";

try {
    // Analyze upload patterns
    $duplicateUploads = DB::table('print_files')
        ->select('file_name', 'print_session_id', DB::raw('COUNT(*) as upload_count'), DB::raw('MIN(created_at) as first_upload'))
        ->groupBy('file_name', 'print_session_id')
        ->having('upload_count', '>', 1)
        ->orderBy('first_upload', 'desc')
        ->get();

    echo "Found " . $duplicateUploads->count() . " cases of duplicate uploads:\n\n";

    foreach ($duplicateUploads as $duplicate) {
        echo "File: {$duplicate->file_name}\n";
        echo "  Session ID: {$duplicate->print_session_id}\n";
        echo "  Upload count: {$duplicate->upload_count}\n";
        echo "  First upload: {$duplicate->first_upload}\n";
        
        // Get detailed info about these files
        $fileDetails = DB::table('print_files')
            ->where('file_name', $duplicate->file_name)
            ->where('print_session_id', $duplicate->print_session_id)
            ->orderBy('created_at')
            ->get();
        
        foreach ($fileDetails as $index => $file) {
            $order = $index + 1;
            echo "    Upload #{$order}: ID {$file->id} at {$file->created_at} ({$file->pages_count} pages)\n";
        }
        echo "\n";
    }

    echo "=== COMMON PATTERNS ===\n";
    
    // Analyze time gaps between duplicate uploads
    echo "1. Time gaps between duplicate uploads:\n";
    foreach ($duplicateUploads as $duplicate) {
        $fileDetails = DB::table('print_files')
            ->where('file_name', $duplicate->file_name)
            ->where('print_session_id', $duplicate->print_session_id)
            ->orderBy('created_at')
            ->get();
        
        if ($fileDetails->count() > 1) {
            $first = $fileDetails->first();
            $last = $fileDetails->last();
            
            $gap = strtotime($last->created_at) - strtotime($first->created_at);
            echo "  {$duplicate->file_name}: {$gap} seconds between first and last upload\n";
        }
    }

    echo "\n2. Most problematic files:\n";
    $problemFiles = DB::table('print_files')
        ->select('file_name', DB::raw('COUNT(*) as total_uploads'))
        ->groupBy('file_name')
        ->having('total_uploads', '>', 2)
        ->orderBy('total_uploads', 'desc')
        ->limit(5)
        ->get();
    
    foreach ($problemFiles as $file) {
        echo "  {$file->file_name}: {$file->total_uploads} total uploads across all sessions\n";
    }

    echo "\n=== RECOMMENDED PREVENTIVE MEASURES ===\n";
    echo "1. 🛡️  Add duplicate file check in uploadFiles() method\n";
    echo "2. 🕐 Implement cooldown period for same file uploads\n";
    echo "3. 🔍 Add frontend validation to prevent same file selection\n";
    echo "4. 📝 Log upload attempts for monitoring\n";
    echo "5. 🚫 Block identical file hash uploads in same session\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}