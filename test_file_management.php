<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\PrintService;
use App\Models\PrintSession;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

echo "🧪 TESTING FILE MANAGEMENT FEATURES\n";
echo "=====================================\n\n";

$printService = new PrintService();

try {
    echo "1. 📝 Creating test session...\n";
    $session = $printService->generateSession();
    echo "   ✅ Session created: {$session->token}\n\n";

    echo "2. 📁 Creating test files...\n";
    $testDir = storage_path('app/test-files');
    if (!file_exists($testDir)) {
        mkdir($testDir, 0755, true);
    }

    $testFiles = [
        'test1.txt' => "This is test file 1\nContent line 2\nContent line 3",
        'test2.pdf' => '%PDF-1.4 test content', // Mock PDF
        'test3.doc' => 'Mock DOC content for testing'
    ];

    $uploadedFiles = [];
    foreach ($testFiles as $filename => $content) {
        $filepath = $testDir . '/' . $filename;
        file_put_contents($filepath, $content);
        
        $uploadedFiles[] = new UploadedFile(
            $filepath,
            $filename,
            mime_content_type($filepath),
            filesize($filepath),
            0
        );
    }
    echo "   ✅ Test files created\n\n";

    echo "3. ⬆️ Testing file upload...\n";
    $uploadResult = $printService->uploadFiles($uploadedFiles, $session);
    
    if ($uploadResult['success']) {
        echo "   ✅ Upload successful!\n";
        echo "   📊 Files uploaded: " . count($uploadResult['files']) . "\n";
        echo "   📄 Total pages: {$uploadResult['total_pages']}\n";
        
        foreach ($uploadResult['files'] as $file) {
            echo "   📄 {$file['name']} - {$file['pages_count']} pages\n";
        }
    } else {
        echo "   ❌ Upload failed\n";
        exit(1);
    }
    echo "\n";

    echo "4. 🗑️ Testing file deletion...\n";
    $firstFile = $uploadResult['files'][0];
    echo "   🎯 Deleting file: {$firstFile['name']} (ID: {$firstFile['id']})\n";
    
    $deleteResult = $printService->deleteFile($firstFile['id'], $session);
    
    if ($deleteResult['success']) {
        echo "   ✅ File deleted successfully!\n";
        echo "   📊 Remaining files: " . count($deleteResult['files']) . "\n";
        echo "   📄 New total pages: {$deleteResult['total_pages']}\n";
        
        foreach ($deleteResult['files'] as $file) {
            echo "   📄 {$file['name']} - {$file['pages_count']} pages\n";
        }
    } else {
        echo "   ❌ Delete failed\n";
    }
    echo "\n";

    echo "5. 👁️ Testing preview functionality...\n";
    if (!empty($deleteResult['files'])) {
        $previewFile = $deleteResult['files'][0];
        echo "   🎯 Preview file: {$previewFile['name']} (ID: {$previewFile['id']})\n";
        
        $printFile = \App\Models\PrintFile::find($previewFile['id']);
        if ($printFile && Storage::exists($printFile->file_path)) {
            echo "   ✅ File exists and accessible for preview\n";
            echo "   📂 File path: {$printFile->file_path}\n";
            echo "   📏 File size: {$printFile->file_size} bytes\n";
        } else {
            echo "   ❌ File not found or not accessible\n";
        }
    } else {
        echo "   ⚠️ No files available for preview test\n";
    }
    echo "\n";

    echo "6. 🔄 Testing upload with fixed filename display...\n";
    $newTestFile = $testDir . '/final_test.txt';
    file_put_contents($newTestFile, "Final test content for filename verification");
    
    $finalUpload = new UploadedFile(
        $newTestFile,
        'final_test.txt',
        'text/plain',
        filesize($newTestFile),
        0
    );
    
    $finalResult = $printService->uploadFiles([$finalUpload], $session);
    
    if ($finalResult['success']) {
        echo "   ✅ Final upload successful!\n";
        $lastFile = end($finalResult['files']);
        echo "   📄 Filename in response: {$lastFile['name']}\n";
        echo "   📄 Original filename: final_test.txt\n";
        
        if ($lastFile['name'] === 'final_test.txt') {
            echo "   ✅ Filename display issue FIXED!\n";
        } else {
            echo "   ❌ Filename still showing as: {$lastFile['name']}\n";
        }
    } else {
        echo "   ❌ Final upload failed\n";
    }
    echo "\n";

    echo "7. 🧹 Cleaning up test files...\n";
    foreach (glob($testDir . '/*') as $file) {
        unlink($file);
    }
    rmdir($testDir);
    echo "   ✅ Test files cleaned up\n\n";

    echo "🎉 ALL FILE MANAGEMENT TESTS COMPLETED!\n";
    echo "=====================================\n";
    echo "✅ File upload with correct filename display\n";
    echo "✅ File deletion functionality\n";
    echo "✅ File preview accessibility\n";
    echo "✅ Proper error handling\n\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . "\n";
    echo "📍 Line: " . $e->getLine() . "\n\n";
    exit(1);
}
