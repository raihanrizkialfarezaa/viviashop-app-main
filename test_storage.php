<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 TESTING STORAGE OPERATIONS\n";
echo "==============================\n\n";

$date = \Carbon\Carbon::now()->format('Y-m-d');
$sessionToken = 'test-session-' . time();
$sessionDir = "print-files/{$date}/{$sessionToken}";

echo "1️⃣ Creating directory: {$sessionDir}\n";
try {
    $result = \Illuminate\Support\Facades\Storage::disk('local')->makeDirectory($sessionDir);
    echo "Directory creation result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
} catch (Exception $e) {
    echo "Directory creation error: " . $e->getMessage() . "\n";
}

echo "\n2️⃣ Creating test file...\n";
$fileName = 'test.txt';
$filePath = "{$sessionDir}/{$fileName}";
$content = "Test file content at " . date('Y-m-d H:i:s');

try {
    $result = \Illuminate\Support\Facades\Storage::disk('local')->put($filePath, $content);
    echo "File creation result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    echo "File path: {$filePath}\n";
} catch (Exception $e) {
    echo "File creation error: " . $e->getMessage() . "\n";
}

echo "\n3️⃣ Checking if file exists...\n";
try {
    $exists = \Illuminate\Support\Facades\Storage::disk('local')->exists($filePath);
    echo "File exists check: " . ($exists ? 'YES' : 'NO') . "\n";
    
    if ($exists) {
        $size = \Illuminate\Support\Facades\Storage::disk('local')->size($filePath);
        echo "File size: {$size} bytes\n";
        
        $fullPath = storage_path('app/' . $filePath);
        echo "Full path: {$fullPath}\n";
        echo "PHP file_exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
    }
} catch (Exception $e) {
    echo "File check error: " . $e->getMessage() . "\n";
}

echo "\n4️⃣ Storage disk info...\n";
$disk = \Illuminate\Support\Facades\Storage::disk('local');
echo "Storage root path: " . storage_path('app') . "\n";

?>
