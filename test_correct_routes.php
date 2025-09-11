<?php
/**
 * Test Admin UI with correct routes
 */

echo "🖥️ TESTING ADMIN UI WITH CORRECT ROUTES\n";
echo "=======================================\n\n";

// Get order ID from database
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::create('/', 'GET')
);

// Connect to database
$order = \App\Models\PrintOrder::orderBy('id', 'desc')->first();

if (!$order) {
    echo "❌ No orders found in database\n";
    exit(1);
}

echo "Testing Order: {$order->order_id}\n";
echo "Customer: {$order->customer_name}\n";
echo "Status: {$order->status}\n\n";

// Get first file ID for testing
$files = $order->files;
if ($files->isEmpty()) {
    echo "❌ No files found for this order\n";
    exit(1);
}

$firstFile = $files->first();
echo "Testing File ID: {$firstFile->id}\n";
echo "File Name: {$firstFile->original_name}\n\n";

// Test the correct admin routes
echo "🧪 TESTING CORRECT ROUTES:\n";
echo "=========================\n\n";

// 1. Test print-files endpoint with correct URL format
echo "1. Testing print-files endpoint:\n";
$url = "http://127.0.0.1:8000/admin/print-service/orders/{$order->id}/print-files";
echo "URL: $url\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
if ($httpCode == 200) {
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($data['files'])) {
        echo "✅ SUCCESS! Files found: " . count($data['files']) . "\n";
        foreach ($data['files'] as $file) {
            echo "   📄 {$file['original_name']}\n";
            echo "   🔗 {$file['view_url']}\n";
        }
    } else {
        echo "Response: $response\n";
    }
} else {
    echo "❌ Failed - HTTP $httpCode\n";
    if ($httpCode == 302) {
        echo "Note: 302 redirect likely means authentication required\n";
    }
}

echo "\n2. Testing view-file endpoint:\n";
$viewUrl = "http://127.0.0.1:8000/admin/print-service/view-file/{$firstFile->id}";
echo "URL: $viewUrl\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $viewUrl);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
if ($httpCode == 200) {
    echo "✅ File viewable directly\n";
} elseif ($httpCode == 302) {
    echo "⚠️ Redirected (likely authentication required)\n";
} else {
    echo "❌ File not accessible - HTTP $httpCode\n";
}

echo "\n📝 ROUTE ANALYSIS:\n";
echo "==================\n";
echo "Correct Admin Print-Files URL Format:\n";
echo "/admin/print-service/orders/{id}/print-files\n\n";
echo "Correct Admin View-File URL Format:\n";
echo "/admin/print-service/view-file/{fileId}\n\n";

echo "🔐 AUTHENTICATION NOTE:\n";
echo "=======================\n";
echo "Admin routes require:\n";
echo "- User authentication (login)\n";
echo "- Admin privileges (is_admin middleware)\n";
echo "- Proper session/CSRF tokens\n\n";

echo "💡 FOR BROWSER TESTING:\n";
echo "=======================\n";
echo "1. Login to admin panel first\n";
echo "2. Navigate to: /admin/print-service/orders\n";
echo "3. Click 'See Files' button on order\n";
echo "4. Files should open in new tabs\n";
echo "5. Use Ctrl+P to print each file\n";
?>
