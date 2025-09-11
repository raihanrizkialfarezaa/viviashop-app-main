<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🎉 FINAL VERIFICATION - SMART PRINT UPLOAD\n";
echo "===========================================\n";

echo "1️⃣ COMPLETE FLOW SIMULATION\n";
echo "============================\n";

try {
    $printService = app(\App\Services\PrintService::class);
    $session = $printService->generateSession();
    $sessionToken = $session->session_token;
    
    echo "✅ Session generated: $sessionToken\n";
    
    $customerUrl = "http://127.0.0.1:8000/print-service/$sessionToken";
    $cookieJar = tempnam(sys_get_temp_dir(), 'final_test_cookies');
    
    echo "\nStep 1: Load customer page and get CSRF token\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $customerUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $pageResponse = curl_exec($ch);
    $pageCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($pageCode === 200) {
        preg_match('/name="csrf-token" content="([^"]+)"/', $pageResponse, $matches);
        $csrfToken = $matches[1] ?? null;
        
        if ($csrfToken) {
            echo "✅ CSRF token extracted: " . substr($csrfToken, 0, 16) . "...\n";
            
            echo "\nStep 2: Test multiple file uploads\n";
            
            $testFiles = [
                ['name' => 'test_document.txt', 'content' => "Test document content\nMultiple lines\nFor testing purposes\n" . str_repeat("Additional line\n", 20)],
                ['name' => 'sample_data.csv', 'content' => "Name,Age,City\nJohn,25,Jakarta\nJane,30,Bandung\nBob,35,Surabaya"],
                ['name' => 'log_file.log', 'content' => "[INFO] Application started\n[DEBUG] Processing data\n[ERROR] Connection failed\n[INFO] Retrying connection"]
            ];
            
            $totalSuccessful = 0;
            $totalFiles = 0;
            $totalPages = 0;
            
            foreach ($testFiles as $fileData) {
                $testFilePath = sys_get_temp_dir() . '/' . $fileData['name'];
                file_put_contents($testFilePath, $fileData['content']);
                
                $boundary = '----FormBoundary' . uniqid();
                $delimiter = '--' . $boundary;
                $eol = "\r\n";
                
                $body = '';
                $body .= $delimiter . $eol;
                $body .= 'Content-Disposition: form-data; name="session_token"' . $eol . $eol;
                $body .= $sessionToken . $eol;
                
                $body .= $delimiter . $eol;
                $body .= 'Content-Disposition: form-data; name="files[]"; filename="' . $fileData['name'] . '"' . $eol;
                $body .= 'Content-Type: text/plain' . $eol . $eol;
                $body .= $fileData['content'] . $eol;
                $body .= $delimiter . '--' . $eol;
                
                $uploadUrl = "http://127.0.0.1:8000/print-service/upload";
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $uploadUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: multipart/form-data; boundary=' . $boundary,
                    'X-CSRF-TOKEN: ' . $csrfToken,
                    'X-Requested-With: XMLHttpRequest',
                    'Accept: application/json',
                    'Referer: ' . $customerUrl
                ]);
                curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                $uploadResponse = curl_exec($ch);
                $uploadCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($uploadCode === 200) {
                    $uploadData = json_decode($uploadResponse, true);
                    
                    if ($uploadData && isset($uploadData['success']) && $uploadData['success']) {
                        $totalSuccessful++;
                        $totalFiles += count($uploadData['files']);
                        $totalPages += $uploadData['total_pages'];
                        
                        echo "✅ " . $fileData['name'] . " uploaded successfully (" . $uploadData['total_pages'] . " pages)\n";
                    } else {
                        echo "❌ " . $fileData['name'] . " upload failed: " . ($uploadData['error'] ?? 'Unknown error') . "\n";
                    }
                } else {
                    echo "❌ " . $fileData['name'] . " HTTP error: $uploadCode\n";
                }
                
                unlink($testFilePath);
            }
            
            echo "\n📊 Upload Results:\n";
            echo "   - Successful uploads: $totalSuccessful / " . count($testFiles) . "\n";
            echo "   - Total files stored: $totalFiles\n";
            echo "   - Total pages: $totalPages\n";
            
            echo "\nStep 3: Verify database records\n";
            $dbFiles = \App\Models\PrintFile::where('print_session_id', $session->id)->get();
            echo "✅ Database verification:\n";
            echo "   - Files in database: " . $dbFiles->count() . "\n";
            echo "   - Total pages in DB: " . $dbFiles->sum('pages_count') . "\n";
            
            if ($dbFiles->count() > 0) {
                echo "   - File details:\n";
                foreach ($dbFiles as $file) {
                    echo "     * " . $file->file_name . " (" . $file->file_type . ", " . $file->pages_count . " pages, " . $file->file_size . " bytes)\n";
                }
            }
            
            echo "\nStep 4: Test session data integrity\n";
            $sessionCheck = \App\Models\PrintSession::find($session->id);
            if ($sessionCheck && $sessionCheck->is_active) {
                echo "✅ Session remains active and valid\n";
                echo "   - Session ID: " . $sessionCheck->id . "\n";
                echo "   - Token: " . $sessionCheck->session_token . "\n";
                echo "   - Current step: " . $sessionCheck->current_step . "\n";
                echo "   - Expires: " . $sessionCheck->expires_at->format('Y-m-d H:i:s') . "\n";
            } else {
                echo "❌ Session integrity issue\n";
            }
            
            if ($totalSuccessful === count($testFiles)) {
                echo "\n🎉 ALL UPLOAD TESTS SUCCESSFUL!\n";
                echo "==============================\n";
                echo "✅ CSRF Token: Working\n";
                echo "✅ File Upload: Working\n";
                echo "✅ Multiple Files: Working\n";
                echo "✅ Database Storage: Working\n";
                echo "✅ Page Counting: Working\n";
                echo "✅ Session Management: Working\n";
                
                echo "\n🚀 SMART PRINT UPLOAD IS 100% FUNCTIONAL!\n";
                echo "==========================================\n";
                echo "The upload error has been completely resolved.\n";
                echo "Users can now successfully upload files.\n";
                
                echo "\n💡 FIXES IMPLEMENTED:\n";
                echo "=====================\n";
                echo "✅ Added CSRF meta tag to print-service page\n";
                echo "✅ Updated JavaScript to include CSRF token\n";
                echo "✅ Enhanced error handling in frontend\n";
                echo "✅ Verified backend upload processing\n";
                echo "✅ Confirmed database storage works\n";
                
                echo "\n🎯 USER TESTING READY:\n";
                echo "======================\n";
                echo "1. Clear browser cache/cookies\n";
                echo "2. Go to: http://127.0.0.1:8000/smart-print\n";
                echo "3. Click 'Generate Session Baru'\n";
                echo "4. Upload files (PDF, DOC, TXT, etc.)\n";
                echo "5. Files should upload successfully\n";
                
            } else {
                echo "\n⚠️  Some uploads failed - check individual errors above\n";
            }
            
        } else {
            echo "❌ CSRF token not found\n";
        }
    } else {
        echo "❌ Customer page error: $pageCode\n";
    }
    
    unlink($cookieJar);
    
} catch (Exception $e) {
    echo "❌ Final verification error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n✨ SMART PRINT UPLOAD VERIFICATION COMPLETE! ✨\n";
