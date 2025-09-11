<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrintOrder;
use App\Models\PrintFile;

echo "🔧 CREATING MISSING FILE\n";
echo "========================\n\n";

$latestOrder = PrintOrder::orderBy('created_at', 'desc')->first();
$file = PrintFile::where('print_order_id', $latestOrder->id)->first();

echo "Order: {$latestOrder->order_code}\n";
echo "File needed: {$file->file_path}\n";

$dir = dirname(storage_path('app/' . $file->file_path));
echo "Creating directory: {$dir}\n";

if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
    echo "✅ Directory created\n";
}

$filePath = storage_path('app/' . $file->file_path);
echo "Creating file: {$filePath}\n";

$pdfContent = "%PDF-1.4
1 0 obj
<<
/Type /Catalog
/Pages 2 0 R
>>
endobj

2 0 obj
<<
/Type /Pages
/Kids [3 0 R]
/Count 1
>>
endobj

3 0 obj
<<
/Type /Page
/Parent 2 0 R
/MediaBox [0 0 612 792]
/Contents 4 0 R
/Resources <<
/Font <<
/F1 5 0 R
>>
>>
>>
endobj

4 0 obj
<<
/Length 44
>>
stream
BT
/F1 12 Tf
72 720 Td
(Test Print File) Tj
ET
endstream
endobj

5 0 obj
<<
/Type /Font
/Subtype /Type1
/BaseFont /Times-Roman
>>
endobj

xref
0 6
0000000000 65535 f 
0000000010 00000 n 
0000000053 00000 n 
0000000110 00000 n 
0000000246 00000 n 
0000000339 00000 n 
trailer
<<
/Size 6
/Root 1 0 R
>>
startxref
412
%%EOF";

file_put_contents($filePath, $pdfContent);
echo "✅ PDF file created\n";

echo "\n🧪 Testing file access:\n";
echo "File exists: " . (file_exists($filePath) ? "✅" : "❌") . "\n";
echo "File size: " . filesize($filePath) . " bytes\n";

echo "\n✅ File creation complete!\n";
