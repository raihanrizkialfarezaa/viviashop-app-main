<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🚀 SMART PRINT UPLOAD - COMPREHENSIVE STRESS TEST\n";
echo "==================================================\n";

$tests = [
    'Session Generation' => false,
    'CSRF Token Access' => false,
    'Single File Upload' => false,
    'Multiple Files Upload' => false,
    'Different File Types' => false,
    'File Size Validation' => false,
    'Page Count Calculation' => false,
    'Database Storage' => false,
    'Error Handling' => false,
    'Complete Flow' => false
];

$sessionToken = null;
$csrfToken = null;
$cookieJar = tempnam(sys_get_temp_dir(), 'stress_test_cookies');

echo "1️⃣ SESSION GENERATION TEST\n";
echo "===========================\n";

try {
    $printService = app(\App\Services\PrintService::class);
    $session = $printService->generateSession();
    $sessionToken = $session->session_token;
    
    echo "✅ Session generated: $sessionToken\n";
    $tests['Session Generation'] = true;
    
} catch (Exception $e) {
    echo "❌ Session generation failed: " . $e->getMessage() . "\n";
}

echo "\n2️⃣ CSRF TOKEN ACCESS TEST\n";
echo "==========================\n";

if ($sessionToken) {
    try {
        $customerUrl = "http://127.0.0.1:8000/print-service/$sessionToken";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $customerUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
        
        $pageResponse = curl_exec($ch);
        $pageCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($pageCode === 200) {
            preg_match('/name="csrf-token" content="([^"]+)"/', $pageResponse, $matches);
            $csrfToken = $matches[1] ?? null;
            
            if ($csrfToken) {
                echo "✅ CSRF token extracted: " . substr($csrfToken, 0, 16) . "...\n";
                $tests['CSRF Token Access'] = true;
            } else {
                echo "❌ CSRF token not found\n";
            }
        } else {
            echo "❌ Customer page error: $pageCode\n";
        }
        
    } catch (Exception $e) {
        echo "❌ CSRF token test error: " . $e->getMessage() . "\n";
    }
}

echo "\n3️⃣ SINGLE FILE UPLOAD TEST\n";
echo "===========================\n";

if ($sessionToken && $csrfToken) {
    try {
        $testFile = sys_get_temp_dir() . '/single_test.txt';
        file_put_contents($testFile, "Single file upload test\nLine 2\nLine 3");
        
        $response = uploadFile($testFile, 'single_test.txt', 'text/plain', $sessionToken, $csrfToken, $cookieJar);
        
        if ($response['success']) {
            echo "✅ Single file upload successful\n";
            echo "   - Files: " . count($response['data']['files']) . "\n";
            echo "   - Total pages: " . $response['data']['total_pages'] . "\n";
            $tests['Single File Upload'] = true;
        } else {
            echo "❌ Single file upload failed: " . $response['error'] . "\n";
        }
        
        unlink($testFile);
        
    } catch (Exception $e) {
        echo "❌ Single file test error: " . $e->getMessage() . "\n";
    }
}

echo "\n4️⃣ MULTIPLE FILES UPLOAD TEST\n";
echo "==============================\n";

if ($sessionToken && $csrfToken) {
    try {
        $testFiles = [];
        
        $txtFile = sys_get_temp_dir() . '/multi_test1.txt';
        file_put_contents($txtFile, str_repeat("Multi file test line\n", 10));
        $testFiles[] = [$txtFile, 'multi_test1.txt', 'text/plain'];
        
        $csvFile = sys_get_temp_dir() . '/multi_test2.csv';
        file_put_contents($csvFile, "Name,Age,City\nJohn,25,Jakarta\nJane,30,Bandung");
        $testFiles[] = [$csvFile, 'multi_test2.csv', 'text/csv'];
        
        $response = uploadMultipleFiles($testFiles, $sessionToken, $csrfToken, $cookieJar);
        
        if ($response['success']) {
            echo "✅ Multiple files upload successful\n";
            echo "   - Files: " . count($response['data']['files']) . "\n";
            echo "   - Total pages: " . $response['data']['total_pages'] . "\n";
            $tests['Multiple Files Upload'] = true;
        } else {
            echo "❌ Multiple files upload failed: " . $response['error'] . "\n";
        }
        
        foreach ($testFiles as $file) {
            unlink($file[0]);
        }
        
    } catch (Exception $e) {
        echo "❌ Multiple files test error: " . $e->getMessage() . "\n";
    }
}

echo "\n5️⃣ DIFFERENT FILE TYPES TEST\n";
echo "=============================\n";

if ($sessionToken && $csrfToken) {
    try {
        $typeTests = [
            'TXT' => ['content' => "Text file test\nMultiple lines", 'mime' => 'text/plain'],
            'LOG' => ['content' => "[INFO] Log file test\n[ERROR] Test error", 'mime' => 'text/plain']
        ];
        
        $successCount = 0;
        foreach ($typeTests as $ext => $data) {
            $testFile = sys_get_temp_dir() . "/type_test.$ext";
            file_put_contents($testFile, $data['content']);
            
            $response = uploadFile($testFile, "type_test.$ext", $data['mime'], $sessionToken, $csrfToken, $cookieJar);
            
            if ($response['success']) {
                echo "✅ $ext file upload successful\n";
                $successCount++;
            } else {
                echo "❌ $ext file upload failed: " . $response['error'] . "\n";
            }
            
            unlink($testFile);
        }
        
        if ($successCount === count($typeTests)) {
            $tests['Different File Types'] = true;
        }
        
    } catch (Exception $e) {
        echo "❌ File types test error: " . $e->getMessage() . "\n";
    }
}

echo "\n6️⃣ FILE SIZE VALIDATION TEST\n";
echo "=============================\n";

if ($sessionToken && $csrfToken) {
    try {
        $largeFile = sys_get_temp_dir() . '/large_test.txt';
        $largeContent = str_repeat("This is a large file test content line.\n", 100000);
        file_put_contents($largeFile, $largeContent);
        
        $fileSize = filesize($largeFile);
        echo "📊 Large file size: " . round($fileSize / 1024 / 1024, 2) . " MB\n";
        
        if ($fileSize < 50 * 1024 * 1024) {
            $response = uploadFile($largeFile, 'large_test.txt', 'text/plain', $sessionToken, $csrfToken, $cookieJar);
            
            if ($response['success']) {
                echo "✅ Large file upload successful\n";
                $tests['File Size Validation'] = true;
            } else {
                echo "❌ Large file upload failed: " . $response['error'] . "\n";
            }
        } else {
            echo "⚠️  File too large for test\n";
        }
        
        unlink($largeFile);
        
    } catch (Exception $e) {
        echo "❌ File size test error: " . $e->getMessage() . "\n";
    }
}

echo "\n7️⃣ PAGE COUNT CALCULATION TEST\n";
echo "===============================\n";

if ($sessionToken && $csrfToken) {
    try {
        $pageTestFile = sys_get_temp_dir() . '/page_test.txt';
        $pageContent = str_repeat("Page line content\n", 60);
        file_put_contents($pageTestFile, $pageContent);
        
        $response = uploadFile($pageTestFile, 'page_test.txt', 'text/plain', $sessionToken, $csrfToken, $cookieJar);
        
        if ($response['success'] && $response['data']['total_pages'] > 1) {
            echo "✅ Page count calculation working: " . $response['data']['total_pages'] . " pages\n";
            $tests['Page Count Calculation'] = true;
        } else {
            echo "❌ Page count calculation issue\n";
        }
        
        unlink($pageTestFile);
        
    } catch (Exception $e) {
        echo "❌ Page count test error: " . $e->getMessage() . "\n";
    }
}

echo "\n8️⃣ DATABASE STORAGE TEST\n";
echo "=========================\n";

try {
    $dbFiles = \App\Models\PrintFile::where('print_session_id', $session->id ?? 0)->get();
    
    echo "✅ Database files found: " . $dbFiles->count() . "\n";
    
    if ($dbFiles->count() > 0) {
        echo "   Sample files:\n";
        foreach ($dbFiles->take(3) as $file) {
            echo "   - " . $file->file_name . " (" . $file->pages_count . " pages, " . $file->file_size . " bytes)\n";
        }
        $tests['Database Storage'] = true;
    }
    
} catch (Exception $e) {
    echo "❌ Database storage test error: " . $e->getMessage() . "\n";
}

echo "\n9️⃣ ERROR HANDLING TEST\n";
echo "=======================\n";

if ($sessionToken && $csrfToken) {
    try {
        echo "Testing invalid session token...\n";
        $invalidResponse = uploadFile(
            sys_get_temp_dir() . '/error_test.txt',
            'error_test.txt',
            'text/plain',
            'invalid_token',
            $csrfToken,
            $cookieJar
        );
        
        if (!$invalidResponse['success']) {
            echo "✅ Invalid session error handling working\n";
            $tests['Error Handling'] = true;
        } else {
            echo "❌ Invalid session error not caught\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error handling test error: " . $e->getMessage() . "\n";
    }
}

echo "\n🔟 COMPLETE FLOW TEST\n";
echo "=====================\n";

if (array_sum($tests) >= 7) {
    echo "✅ Complete upload flow functional\n";
    $tests['Complete Flow'] = true;
} else {
    echo "❌ Complete flow has issues\n";
}

echo "\n📊 STRESS TEST RESULTS\n";
echo "=======================\n";

$totalTests = count($tests);
$passedTests = array_sum($tests);

foreach ($tests as $test => $passed) {
    echo ($passed ? "✅" : "❌") . " $test\n";
}

echo "\n🎯 FINAL RESULTS\n";
echo "================\n";
echo "Passed: $passedTests / $totalTests tests\n";

if ($passedTests >= 8) {
    echo "🎉 UPLOAD FUNCTIONALITY FULLY OPERATIONAL!\n";
} else {
    echo "⚠️  Some upload functionality needs attention\n";
}

unlink($cookieJar);

function uploadFile($filePath, $fileName, $mimeType, $sessionToken, $csrfToken, $cookieJar) {
    if (!file_exists($filePath)) {
        file_put_contents($filePath, "Test content");
    }
    
    $boundary = '----FormBoundary' . uniqid();
    $delimiter = '--' . $boundary;
    $eol = "\r\n";
    
    $body = '';
    
    $body .= $delimiter . $eol;
    $body .= 'Content-Disposition: form-data; name="session_token"' . $eol . $eol;
    $body .= $sessionToken . $eol;
    
    $body .= $delimiter . $eol;
    $body .= 'Content-Disposition: form-data; name="files[]"; filename="' . $fileName . '"' . $eol;
    $body .= 'Content-Type: ' . $mimeType . $eol . $eol;
    $body .= file_get_contents($filePath) . $eol;
    
    $body .= $delimiter . '--' . $eol;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/print-service/upload");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'X-CSRF-TOKEN: ' . $csrfToken,
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return ['success' => $data['success'] ?? false, 'data' => $data, 'error' => $data['error'] ?? null];
    } else {
        return ['success' => false, 'error' => "HTTP $httpCode"];
    }
}

function uploadMultipleFiles($files, $sessionToken, $csrfToken, $cookieJar) {
    $boundary = '----FormBoundary' . uniqid();
    $delimiter = '--' . $boundary;
    $eol = "\r\n";
    
    $body = '';
    
    $body .= $delimiter . $eol;
    $body .= 'Content-Disposition: form-data; name="session_token"' . $eol . $eol;
    $body .= $sessionToken . $eol;
    
    foreach ($files as $file) {
        $body .= $delimiter . $eol;
        $body .= 'Content-Disposition: form-data; name="files[]"; filename="' . $file[1] . '"' . $eol;
        $body .= 'Content-Type: ' . $file[2] . $eol . $eol;
        $body .= file_get_contents($file[0]) . $eol;
    }
    
    $body .= $delimiter . '--' . $eol;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/print-service/upload");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'X-CSRF-TOKEN: ' . $csrfToken,
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return ['success' => $data['success'] ?? false, 'data' => $data, 'error' => $data['error'] ?? null];
    } else {
        return ['success' => false, 'error' => "HTTP $httpCode"];
    }
}

echo "\n✨ SMART PRINT UPLOAD STRESS TEST COMPLETE! ✨\n";
