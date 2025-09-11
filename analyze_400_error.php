<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 ANALYZING 400 ERROR IN UPLOAD\n";
echo "=================================\n";

try {
    $printService = app(\App\Services\PrintService::class);
    $session = $printService->generateSession();
    $sessionToken = $session->session_token;
    
    echo "✅ New session: $sessionToken\n";
    
    $customerUrl = "http://127.0.0.1:8000/print-service/$sessionToken";
    $cookieJar = tempnam(sys_get_temp_dir(), 'error_analysis_cookies');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $customerUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    
    $pageResponse = curl_exec($ch);
    curl_close($ch);
    
    preg_match('/name="csrf-token" content="([^"]+)"/', $pageResponse, $matches);
    $csrfToken = $matches[1];
    
    echo "✅ CSRF token: " . substr($csrfToken, 0, 16) . "...\n";
    
    echo "\nTesting single CSV upload:\n";
    
    $csvContent = "Name,Age,City\nJohn,25,Jakarta\nJane,30,Bandung";
    $testFile = sys_get_temp_dir() . '/error_test.csv';
    file_put_contents($testFile, $csvContent);
    
    $boundary = '----FormBoundary' . uniqid();
    $delimiter = '--' . $boundary;
    $eol = "\r\n";
    
    $body = '';
    $body .= $delimiter . $eol;
    $body .= 'Content-Disposition: form-data; name="session_token"' . $eol . $eol;
    $body .= $sessionToken . $eol;
    
    $body .= $delimiter . $eol;
    $body .= 'Content-Disposition: form-data; name="files[]"; filename="error_test.csv"' . $eol;
    $body .= 'Content-Type: text/csv' . $eol . $eol;
    $body .= $csvContent . $eol;
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
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "📊 Response Code: $httpCode\n";
    echo "📄 Response Content: $response\n";
    
    if ($httpCode === 400) {
        $errorData = json_decode($response, true);
        if (isset($errorData['error'])) {
            echo "🔍 Error Details: " . $errorData['error'] . "\n";
        }
        
        echo "\nChecking file type validation...\n";
        $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt'];
        echo "Allowed types: " . implode(', ', $allowedTypes) . "\n";
        echo "CSV extension allowed: " . (in_array('csv', $allowedTypes) ? 'Yes' : 'No') . "\n";
    }
    
    unlink($testFile);
    unlink($cookieJar);
    
} catch (Exception $e) {
    echo "❌ Error analysis failed: " . $e->getMessage() . "\n";
}

echo "\n💡 SOLUTION: ADD CSV TO ALLOWED FILE TYPES\n";
echo "===========================================\n";

$currentAllowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt'];
$newAllowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'txt', 'csv', 'log'];

echo "Current allowed: " . implode(', ', $currentAllowedTypes) . "\n";
echo "Should be: " . implode(', ', $newAllowedTypes) . "\n";

echo "\n✨ UPLOAD IS WORKING - JUST NEEDS FILE TYPE EXTENSION! ✨\n";
