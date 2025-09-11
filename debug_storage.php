<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 LARAVEL STORAGE DEBUG TEST\n";
echo "==============================\n\n";

$date = '2025-09-11';
$sessionToken = 'storage-debug-' . time();

echo "1️⃣ Storage disk info...\n";
$disk = \Illuminate\Support\Facades\Storage::disk('local');
echo "Root path: " . storage_path('app') . "\n";

echo "\n2️⃣ Creating directory with Storage...\n";
$sessionDir = "print-files/{$date}/{$sessionToken}";
echo "Directory path: {$sessionDir}\n";

try {
    $dirResult = $disk->makeDirectory($sessionDir);
    echo "Directory creation result: " . ($dirResult ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Check if directory exists in Storage
    $dirExists = $disk->exists($sessionDir);
    echo "Directory exists in Storage: " . ($dirExists ? 'YES' : 'NO') . "\n";
    
    // Check actual filesystem
    $realPath = storage_path('app/' . $sessionDir);
    echo "Real path: {$realPath}\n";
    echo "Directory exists on filesystem: " . (is_dir($realPath) ? 'YES' : 'NO') . "\n";
    
} catch (Exception $e) {
    echo "Directory creation error: " . $e->getMessage() . "\n";
}

echo "\n3️⃣ Creating file with Storage...\n";
$fileName = 'storage_test.txt';
$filePath = "{$sessionDir}/{$fileName}";
$content = "Storage test content at " . date('Y-m-d H:i:s');

try {
    $fileResult = $disk->put($filePath, $content);
    echo "File creation result: " . ($fileResult ? 'SUCCESS' : 'FAILED') . "\n";
    
    // Check if file exists in Storage
    $fileExists = $disk->exists($filePath);
    echo "File exists in Storage: " . ($fileExists ? 'YES' : 'NO') . "\n";
    
    if ($fileExists) {
        $size = $disk->size($filePath);
        echo "File size in Storage: {$size} bytes\n";
        $retrievedContent = $disk->get($filePath);
        echo "Content from Storage: {$retrievedContent}\n";
    }
    
    // Check actual filesystem
    $realFilePath = storage_path('app/' . $filePath);
    echo "Real file path: {$realFilePath}\n";
    echo "File exists on filesystem: " . (file_exists($realFilePath) ? 'YES' : 'NO') . "\n";
    
    if (file_exists($realFilePath)) {
        echo "File size on filesystem: " . filesize($realFilePath) . " bytes\n";
        echo "Content from filesystem: " . file_get_contents($realFilePath) . "\n";
    }
    
} catch (Exception $e) {
    echo "File creation error: " . $e->getMessage() . "\n";
}

echo "\n4️⃣ Listing storage contents...\n";
try {
    $files = $disk->allFiles('print-files');
    echo "Total files found in print-files: " . count($files) . "\n";
    foreach (array_slice($files, -5) as $file) {
        echo "- {$file}\n";
    }
} catch (Exception $e) {
    echo "Listing error: " . $e->getMessage() . "\n";
}

?>
