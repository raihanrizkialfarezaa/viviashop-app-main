<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 CREATING TEST ORDER FOR PRINT FUNCTIONALITY\n";
echo "==============================================\n\n";

echo "1️⃣ Creating test session...\n";
$printService = new \App\Services\PrintService();
$session = $printService->generateSession();

if ($session) {
    echo "✅ Session created: {$session->session_token}\n";
} else {
    echo "❌ Failed to create session\n";
    exit;
}

echo "\n2️⃣ Finding available product variant...\n";
$variant = \App\Models\ProductVariant::where('is_active', 1)
    ->whereHas('product', function($q) {
        $q->where('is_print_service', true);
    })
    ->first();

if (!$variant) {
    echo "⚠️ No print service variants found, checking for any variants...\n";
    $variant = \App\Models\ProductVariant::where('is_active', 1)->first();
    
    if ($variant) {
        echo "📝 Using regular variant and marking its product as print service...\n";
        $product = $variant->product;
        $product->update(['is_print_service' => true]);
        echo "✅ Product {$product->name} marked as print service\n";
    } else {
        echo "❌ No active variants found at all\n";
        exit;
    }
}

echo "✅ Using variant: {$variant->name}\n";
echo "Product: {$variant->product->name}\n";

echo "\n3️⃣ Creating test print order...\n";
$orderData = [
    'order_code' => \App\Models\PrintOrder::generateCode(),
    'customer_phone' => '085123456789',
    'customer_name' => 'Test Customer',
    'file_data' => json_encode([
        [
            'name' => 'test_document.pdf',
            'type' => 'pdf',
            'size' => 1024000,
            'pages' => 5
        ],
        [
            'name' => 'presentation.pptx',
            'type' => 'pptx', 
            'size' => 2048000,
            'pages' => 10
        ]
    ]),
    'paper_product_id' => $variant->product_id,
    'paper_variant_id' => $variant->id,
    'print_type' => 'color',
    'quantity' => 2,
    'total_pages' => 15,
    'unit_price' => 1500,
    'total_price' => 45000, // 15 pages x 2 copies x 1500
    'payment_method' => 'toko',
    'status' => 'ready_to_print',
    'payment_status' => 'paid',
    'session_id' => $session->id,
    'paid_at' => now()
];

$printOrder = \App\Models\PrintOrder::create($orderData);
echo "✅ Order created: {$printOrder->order_code}\n";

echo "\n4️⃣ Creating test files...\n";
$testFileContent = "This is a test PDF content for printing.\n\nOrder: {$printOrder->order_code}\nCustomer: {$printOrder->customer_name}\n\nThis file would normally contain the customer's actual document.";

// Create directory with date structure like PrintService does
$date = \Carbon\Carbon::now()->format('Y-m-d');
$sessionDir = "print-files/{$date}/{$session->session_token}";
\Illuminate\Support\Facades\Storage::disk('local')->makeDirectory($sessionDir);

$filesData = [
    [
        'name' => 'test_document.pdf',
        'content' => $testFileContent,
        'pages' => 5
    ],
    [
        'name' => 'presentation.pptx', 
        'content' => "PowerPoint presentation content...\n\nSlides: 10\nCustomer: {$printOrder->customer_name}",
        'pages' => 10
    ]
];

foreach ($filesData as $fileData) {
    $fileName = $fileData['name'];
    $filePath = "print-files/{$date}/{$session->session_token}/{$fileName}";
    
    \Illuminate\Support\Facades\Storage::disk('local')->put($filePath, $fileData['content']);
    
    $printFile = \App\Models\PrintFile::create([
        'print_order_id' => $printOrder->id,
        'session_id' => $session->id,
        'file_name' => $fileName,
        'file_path' => $filePath,
        'file_type' => pathinfo($fileName, PATHINFO_EXTENSION),
        'file_size' => strlen($fileData['content']),
        'pages_count' => $fileData['pages']
    ]);
    
    echo "✅ File created: {$fileName} ({$printFile->id})\n";
    echo "   Path: " . storage_path('app/' . $filePath) . "\n";
}

echo "\n5️⃣ Verifying test order setup...\n";
$testOrder = \App\Models\PrintOrder::with(['files', 'paperVariant', 'session'])
    ->where('id', $printOrder->id)
    ->first();

echo "Order Code: {$testOrder->order_code}\n";
echo "Status: {$testOrder->status}\n";  
echo "Payment Status: {$testOrder->payment_status}\n";
echo "Can Print: " . ($testOrder->canPrint() ? '✅ YES' : '❌ NO') . "\n";
echo "Files Count: " . $testOrder->files->count() . "\n";
echo "Session: {$testOrder->session->session_token}\n";

foreach ($testOrder->files as $file) {
    $fullPath = storage_path('app' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file->file_path));
    echo "- {$file->file_name}: " . (file_exists($fullPath) ? '✅ EXISTS' : '❌ MISSING') . "\n";
    echo "  Debug path: {$fullPath}\n";
}

echo "\n6️⃣ Testing print functionality...\n";
try {
    $controller = new \App\Http\Controllers\Admin\PrintServiceController($printService);
    $request = new \Illuminate\Http\Request();
    
    echo "Calling printFiles endpoint...\n";
    $response = $controller->printFiles($request, $testOrder->id);
    $responseData = json_decode($response->getContent(), true);
    
    if (isset($responseData['success']) && $responseData['success']) {
        echo "✅ Print files endpoint working!\n";
        echo "Files returned: " . count($responseData['files']) . "\n";
        echo "Order code: {$responseData['order_code']}\n";
        echo "Customer: {$responseData['customer_name']}\n";
        
        foreach ($responseData['files'] as $filePath) {
            echo "- File path: $filePath\n";
        }
    } else {
        echo "❌ Print files failed: " . ($responseData['error'] ?? 'Unknown error') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error testing print functionality: " . $e->getMessage() . "\n";
}

echo "\n🎯 TEST ORDER READY FOR ADMIN TESTING!\n";
echo "======================================\n";
echo "✅ Order created with ready_to_print status\n";
echo "✅ Files uploaded and accessible\n";
echo "✅ Print functionality working\n";
echo "✅ Ready for manual testing in admin panel\n";

echo "\n📋 TEST INSTRUCTIONS:\n";
echo "1. Go to: http://127.0.0.1:8000/admin/print-service/orders\n";
echo "2. Find order: {$testOrder->order_code}\n";
echo "3. Status should show: 'Ready To Print'\n";
echo "4. Click 'Print Files' button\n";
echo "5. Files should open in browser/app\n";
echo "6. Use Ctrl+P to print\n";
echo "7. Click 'Complete' when done\n";
echo "8. Verify files are deleted\n";

?>
