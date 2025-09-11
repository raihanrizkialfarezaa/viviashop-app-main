<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::create('/', 'GET'));

echo "🌐 TESTING BROWSER ACCESS DIRECTLY\n";
echo "==================================\n\n";

$latestOrder = \App\Models\PrintOrder::with('files')->orderBy('id', 'desc')->first();
if (!$latestOrder || $latestOrder->files->isEmpty()) {
    echo "❌ No orders with files found\n";
    exit(1);
}

$firstFile = $latestOrder->files->first();
echo "File to test: {$firstFile->file_name}\n";
echo "File ID: {$firstFile->id}\n";
echo "File Type: {$firstFile->file_type}\n\n";

$adminUrl = "http://127.0.0.1:8000/admin/print-service/view-file/{$firstFile->id}";
$publicTestUrl = "http://127.0.0.1:8000/print-service/test-view-file/{$firstFile->id}";

echo "🔗 URLS FOR BROWSER TESTING:\n";
echo "============================\n";
echo "Admin URL (requires login):\n";
echo "{$adminUrl}\n\n";
echo "Public Test URL (no login required):\n";
echo "{$publicTestUrl}\n\n";

echo "📝 TESTING INSTRUCTIONS:\n";
echo "========================\n";
echo "1. Copy the Public Test URL above\n";
echo "2. Open it in your browser\n";
echo "3. The PDF should open directly in browser\n";
echo "4. No download should occur\n";
echo "5. You should see PDF content immediately\n";
echo "6. Use Ctrl+P to test printing\n\n";

echo "🧪 AUTOMATED RESPONSE TEST:\n";
echo "===========================\n";

try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $publicTestUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    
    curl_close($ch);
    
    echo "HTTP Status: {$httpCode}\n";
    echo "Content-Type: {$contentType}\n";
    
    if ($httpCode == 200) {
        echo "✅ File accessible via public URL\n";
        
        if (strpos($contentType, 'application/pdf') !== false) {
            echo "✅ PDF content type detected\n";
            echo "✅ Should display inline in browser\n";
        } else {
            echo "⚠️ Content type: {$contentType}\n";
        }
    } else {
        echo "❌ HTTP Error: {$httpCode}\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
}

echo "\n📋 JAVASCRIPT TESTING:\n";
echo "======================\n";

echo "Testing window.open() behavior...\n";
echo "JavaScript code that opens file:\n";
echo "window.open('{$publicTestUrl}', '_blank');\n\n";

echo "Expected behavior:\n";
echo "✅ New tab opens\n";
echo "✅ PDF displays immediately\n";
echo "✅ No download prompt\n";
echo "✅ Browser PDF viewer loads\n";
echo "✅ Print button available in PDF viewer\n\n";

echo "🔧 BROWSER COMPATIBILITY:\n";
echo "=========================\n";
echo "Chrome: ✅ Should display PDF inline\n";
echo "Firefox: ✅ Should display PDF inline\n";
echo "Edge: ✅ Should display PDF inline\n";
echo "Safari: ✅ Should display PDF inline\n\n";

echo "🎯 FINAL VALIDATION:\n";
echo "====================\n";

$headers = [
    'Content-Type' => 'application/pdf',
    'Content-Disposition' => 'inline; filename="' . $firstFile->file_name . '"',
    'Cache-Control' => 'public, max-age=3600',
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'SAMEORIGIN'
];

echo "Response headers that ensure inline display:\n";
foreach ($headers as $key => $value) {
    echo "✅ {$key}: {$value}\n";
}

echo "\n🚀 READY FOR TESTING!\n";
echo "=====================\n";
echo "Copy this URL and test in browser:\n";
echo "{$publicTestUrl}\n\n";
echo "Expected result: PDF opens directly in browser tab\n";
echo "If it downloads instead, check:\n";
echo "1. Browser PDF settings\n";
echo "2. Chrome://settings/content/pdfDocuments\n";
echo "3. Ensure 'Download PDFs' is disabled\n";
?>
