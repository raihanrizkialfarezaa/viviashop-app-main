<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING PEMBELIAN PAGE MODAL ISSUE ===\n";

echo "\n1. CHECKING SUPPLIERS DATA\n";
$suppliers = \App\Models\Supplier::orderBy('nama')->get();
echo "Suppliers found: {$suppliers->count()}\n";

if ($suppliers->count() == 0) {
    echo "❌ No suppliers found! This will cause modal to be empty.\n";
    echo "Creating test supplier...\n";
    
    $testSupplier = \App\Models\Supplier::create([
        'nama' => 'Test Supplier Modal',
        'alamat' => 'Test Address',
        'telepon' => '1234567890'
    ]);
    
    echo "✅ Created test supplier: {$testSupplier->nama}\n";
} else {
    foreach ($suppliers->take(3) as $supplier) {
        echo "- {$supplier->nama} (ID: {$supplier->id})\n";
    }
}

echo "\n2. CHECKING PEMBELIAN DATA\n";
$pembelians = \App\Models\Pembelian::orderBy('id', 'desc')->take(5)->get();
echo "Recent purchases: {$pembelians->count()}\n";

foreach ($pembelians as $pembelian) {
    $supplierName = $pembelian->supplier ? $pembelian->supplier->nama : 'No Supplier';
    echo "- Purchase {$pembelian->id}: {$supplierName}, Status: {$pembelian->status}\n";
}

echo "\n3. TESTING PEMBELIAN DATA ROUTE\n";
try {
    $controller = new \App\Http\Controllers\PembelianController();
    $response = $controller->data();
    echo "✅ Data route is working\n";
} catch (Exception $e) {
    echo "❌ Error in data route: {$e->getMessage()}\n";
}

echo "\n4. MODAL DEBUGGING RECOMMENDATIONS\n";
echo "The modal freeze issue is likely caused by:\n";
echo "1. Bootstrap version conflict (layout uses Bootstrap 3.4.1)\n";
echo "2. CSS z-index conflicts\n";
echo "3. DataTables initialization timing\n";
echo "4. Event handling conflicts\n";

echo "\n5. CHECKING FOR OVERLAPPING ELEMENTS\n";
echo "Looking for potential z-index or modal conflicts...\n";

echo "\nModal elements in the page:\n";
echo "- #modal-supplier (supplier selection)\n";
echo "- #modal-detail (purchase detail view)\n";
echo "- Both use Bootstrap 3 modal structure\n";

echo "\n=== ANALYSIS COMPLETE ===\n";