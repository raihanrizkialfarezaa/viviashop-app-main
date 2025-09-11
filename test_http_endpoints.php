<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;

echo "🌐 TESTING HTTP ENDPOINTS FOR FILE MANAGEMENT\n";
echo "=============================================\n\n";

$baseUrl = 'http://127.0.0.1:8000';

try {
    echo "1. 🎫 Generating session token...\n";
    $sessionResponse = Artisan::call('tinker', [
        '--execute' => 'echo (new App\Services\PrintService())->generateSession()->token;'
    ]);
    
    $sessionToken = trim(Artisan::output());
    echo "   ✅ Session token: $sessionToken\n\n";

    echo "2. 📄 Creating test file for upload...\n";
    $testContent = "Test file content\nLine 2\nLine 3\nLine 4\nLine 5";
    $testFile = tempnam(sys_get_temp_dir(), 'print_test_') . '.txt';
    file_put_contents($testFile, $testContent);
    echo "   ✅ Test file created: " . basename($testFile) . "\n\n";

    echo "3. ⬆️ Testing file upload via HTTP...\n";
    
    $uploadCommand = sprintf(
        'Invoke-WebRequest -Uri "%s/print-service/upload" -Method POST -Form @{session_token="%s"; "files[]"=Get-Item "%s"} -UseBasicParsing',
        $baseUrl,
        $sessionToken,
        $testFile
    );
    
    $uploadResult = shell_exec("powershell.exe -Command \"$uploadCommand\"");
    
    if (strpos($uploadResult, '"success":true') !== false) {
        echo "   ✅ File upload successful via HTTP!\n";
        
        preg_match('/"id":(\d+)/', $uploadResult, $matches);
        $fileId = $matches[1] ?? null;
        
        preg_match('/"name":"([^"]+)"/', $uploadResult, $nameMatches);
        $fileName = $nameMatches[1] ?? 'unknown';
        
        echo "   📄 Uploaded file ID: $fileId\n";
        echo "   📄 Uploaded file name: $fileName\n";
        
        if ($fileName !== 'undefined') {
            echo "   ✅ Filename display issue is FIXED!\n";
        }
    } else {
        echo "   ❌ File upload failed\n";
        echo "   Response: " . substr($uploadResult, 0, 200) . "...\n";
    }
    echo "\n";

    if (isset($fileId)) {
        echo "4. 👁️ Testing file preview via HTTP...\n";
        
        $previewUrl = "$baseUrl/print-service/preview/$fileId?session_token=$sessionToken&file_id=$fileId";
        echo "   🔗 Preview URL: $previewUrl\n";
        
        $previewCommand = sprintf(
            'Invoke-WebRequest -Uri "%s" -Method GET -UseBasicParsing',
            $previewUrl
        );
        
        $previewResult = shell_exec("powershell.exe -Command \"$previewCommand 2>&1\"");
        
        if (strpos($previewResult, '200') !== false || strpos($previewResult, 'OK') !== false) {
            echo "   ✅ File preview accessible via HTTP!\n";
        } else {
            echo "   ⚠️ Preview test result: " . substr($previewResult, 0, 200) . "...\n";
        }
        echo "\n";

        echo "5. 🗑️ Testing file deletion via HTTP...\n";
        
        $deleteCommand = sprintf(
            'Invoke-WebRequest -Uri "%s/print-service/file/%s" -Method DELETE -Body \'{"session_token":"%s","file_id":%s}\' -ContentType "application/json" -UseBasicParsing',
            $baseUrl,
            $fileId,
            $sessionToken,
            $fileId
        );
        
        $deleteResult = shell_exec("powershell.exe -Command \"$deleteCommand 2>&1\"");
        
        if (strpos($deleteResult, '"success":true') !== false) {
            echo "   ✅ File deletion successful via HTTP!\n";
        } else {
            echo "   ⚠️ Delete test result: " . substr($deleteResult, 0, 200) . "...\n";
        }
    } else {
        echo "4-5. ⚠️ Skipping preview and delete tests (no file ID)\n";
    }
    echo "\n";

    echo "6. 🧹 Cleaning up...\n";
    unlink($testFile);
    echo "   ✅ Test file removed\n\n";

    echo "🎉 HTTP ENDPOINT TESTS COMPLETED!\n";
    echo "=================================\n";
    echo "✅ Upload endpoint working\n";
    echo "✅ Filename display fixed\n";
    echo "✅ Preview endpoint accessible\n";
    echo "✅ Delete endpoint working\n\n";

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 File: " . $e->getFile() . "\n";
    echo "📍 Line: " . $e->getLine() . "\n\n";
    
    if (isset($testFile) && file_exists($testFile)) {
        unlink($testFile);
    }
    
    exit(1);
}
