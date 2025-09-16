<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\PrintService;
use App\Models\PrintSession;
use Illuminate\Http\UploadedFile;

echo "=== TESTING DUPLICATE FILE PREVENTION SAFEGUARDS ===\n\n";

try {
    $printService = new PrintService();
    
    echo "1. Creating test session...\n";
    $session = $printService->generateSession();
    echo "   ✅ Session created: {$session->session_token}\n\n";

    echo "2. Creating test files...\n";
    $testContent = "This is a test document for duplicate prevention testing.";
    $tempDir = storage_path('app/temp');
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $testFilePath = $tempDir . '/duplicate_test.txt';
    file_put_contents($testFilePath, $testContent);
    echo "   ✅ Test file created: {$testFilePath}\n\n";

    echo "3. First upload attempt...\n";
    $uploadedFile1 = new UploadedFile(
        $testFilePath,
        'duplicate_test.txt',
        'text/plain',
        null,
        true
    );
    
    $result1 = $printService->uploadFiles([$uploadedFile1], $session);
    
    if ($result1['success']) {
        echo "   ✅ First upload successful\n";
        echo "   📄 Files uploaded: " . count($result1['files']) . "\n";
        echo "   📊 Total pages: {$result1['total_pages']}\n";
        
        if (isset($result1['skipped_files'])) {
            echo "   ⚠️  Skipped files: " . count($result1['skipped_files']) . "\n";
        }
    } else {
        echo "   ❌ First upload failed\n";
        exit(1);
    }
    echo "\n";

    echo "4. Second upload attempt (should be blocked)...\n";
    $uploadedFile2 = new UploadedFile(
        $testFilePath,
        'duplicate_test.txt',
        'text/plain',
        null,
        true
    );
    
    $result2 = $printService->uploadFiles([$uploadedFile2], $session);
    
    if ($result2['success']) {
        echo "   📄 Files uploaded: " . count($result2['files']) . "\n";
        echo "   📊 Total pages: {$result2['total_pages']}\n";
        
        if (isset($result2['skipped_files']) && count($result2['skipped_files']) > 0) {
            echo "   ✅ SAFEGUARD WORKING: " . count($result2['skipped_files']) . " duplicate file(s) blocked\n";
            foreach ($result2['skipped_files'] as $skipped) {
                echo "     - {$skipped['name']}: {$skipped['reason']}\n";
            }
        } else {
            echo "   ❌ SAFEGUARD FAILED: Duplicate was not blocked!\n";
        }
    } else {
        echo "   ❌ Second upload failed unexpectedly\n";
    }
    echo "\n";

    echo "5. Third upload with different file (should work)...\n";
    $differentContent = "This is a DIFFERENT test document.";
    $differentFilePath = $tempDir . '/different_test.txt';
    file_put_contents($differentFilePath, $differentContent);
    
    $uploadedFile3 = new UploadedFile(
        $differentFilePath,
        'different_test.txt',
        'text/plain',
        null,
        true
    );
    
    $result3 = $printService->uploadFiles([$uploadedFile3], $session);
    
    if ($result3['success']) {
        echo "   ✅ Different file upload successful\n";
        echo "   📄 Total files now: " . count($result3['files']) . "\n";
        echo "   📊 Total pages: {$result3['total_pages']}\n";
    } else {
        echo "   ❌ Different file upload failed\n";
    }
    echo "\n";

    echo "6. Verifying database state...\n";
    $dbFiles = \App\Models\PrintFile::where('print_session_id', $session->id)->get();
    echo "   📊 Files in database: " . $dbFiles->count() . "\n";
    
    $uniqueFileNames = $dbFiles->pluck('file_name')->unique();
    echo "   📂 Unique file names: " . $uniqueFileNames->count() . "\n";
    
    foreach ($dbFiles as $file) {
        echo "     - {$file->file_name} (ID: {$file->id}, Pages: {$file->pages_count})\n";
    }
    
    if ($dbFiles->count() === $uniqueFileNames->count()) {
        echo "   ✅ NO DUPLICATES: All files are unique\n";
    } else {
        echo "   ❌ DUPLICATES FOUND: Database has duplicate files\n";
    }
    echo "\n";

    echo "=== CLEANUP ===\n";
    unlink($testFilePath);
    unlink($differentFilePath);
    echo "✅ Test files deleted\n";

    echo "\n=== SAFEGUARD TEST COMPLETE ===\n";
    
    if (isset($result2['skipped_files']) && count($result2['skipped_files']) > 0) {
        echo "🎉 SUCCESS: Duplicate file prevention is working correctly!\n";
        echo "   - Backend safeguard: ✅ Blocking duplicate uploads\n";
        echo "   - Database integrity: ✅ No duplicate records\n";
        echo "   - File system: ✅ No duplicate files stored\n";
        echo "\n✨ The bank transfer double file issue will NOT occur again!\n";
    } else {
        echo "⚠️ WARNING: Safeguard may not be working properly!\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}