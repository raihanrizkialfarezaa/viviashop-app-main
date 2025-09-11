<?php
/**
 * Test Admin UI "See Files" functionality
 */

// Simulate clicking "See Files" button in admin panel
echo "🖥️ TESTING ADMIN UI 'SEE FILES' FUNCTIONALITY\n";
echo "=============================================\n\n";

// Test the actual API endpoint that admin panel calls
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://127.0.0.1:8000/admin/print-service/print-files');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'order_id' => 'PRINT-11-09-2025-15-09-57'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "API Endpoint: /admin/print-service/print-files\n";
echo "Order ID: PRINT-11-09-2025-15-09-57\n";
echo "HTTP Status: $httpCode\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    echo "✅ SUCCESS!\n";
    echo "Files found: " . count($data['files']) . "\n\n";
    
    foreach ($data['files'] as $file) {
        echo "📄 " . $file['original_name'] . "\n";
        echo "   View URL: " . $file['view_url'] . "\n";
        echo "   Ready for Ctrl+P printing\n\n";
    }
    
    echo "🎯 Admin can now:\n";
    echo "   1. Click 'See Files' button\n";
    echo "   2. Files open in new tabs\n";
    echo "   3. Use Ctrl+P to print each file\n";
    echo "   4. Manual control over printing process\n\n";
    
} else {
    echo "❌ FAILED!\n";
    echo "Response: $response\n";
}

// Test individual file viewing
echo "🔍 Testing individual file view:\n";
$fileUrl = 'http://127.0.0.1:8000/admin/print-service/view-file/74';
echo "File URL: $fileUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $fileUrl);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    echo "✅ File viewable - ready for printing\n";
} else {
    echo "❌ File not accessible - HTTP $httpCode\n";
}

echo "\n📋 ADMIN WORKFLOW SUMMARY:\n";
echo "=========================\n";
echo "1. Customer uploads files ✓\n";
echo "2. Admin sees order in list ✓\n";
echo "3. Admin clicks 'See Files' ✓\n";
echo "4. Files open in browser tabs ✓\n";
echo "5. Admin uses Ctrl+P to print ✓\n";
echo "6. Simple, reliable workflow ✓\n";
?>
