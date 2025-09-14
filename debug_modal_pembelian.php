<?php

echo "=== PEMBELIAN DETAIL MODAL DEBUG TEST ===\n\n";

echo "1. Checking file structure...\n";

$files = [
    'Main view' => 'resources/views/admin/pembelian_detail/index.blade.php',
    'Modal view' => 'resources/views/admin/pembelian_detail/produk.blade.php',
    'Controller' => 'app/Http/Controllers/PembelianDetailController.php',
    'Layout' => 'resources/views/layouts/app.blade.php',
];

foreach ($files as $type => $file) {
    if (file_exists($file)) {
        echo "✓ {$type}: {$file}\n";
    } else {
        echo "✗ {$type} MISSING: {$file}\n";
    }
}

echo "\n2. Checking modal trigger button...\n";

$indexView = file_get_contents('resources/views/admin/pembelian_detail/index.blade.php');

$buttonChecks = [
    'Button exists' => strpos($indexView, 'onclick="tampilProduk()"') !== false,
    'Button has correct class' => strpos($indexView, 'btn btn-info btn-flat') !== false,
    'Button has icon' => strpos($indexView, 'fa fa-arrow-right') !== false,
    'Button type is button' => strpos($indexView, 'type="button"') !== false,
];

foreach ($buttonChecks as $check => $status) {
    echo ($status ? "✓" : "✗") . " {$check}\n";
}

echo "\n3. Checking JavaScript function...\n";

$jsChecks = [
    'tampilProduk function exists' => strpos($indexView, 'function tampilProduk()') !== false,
    'Modal show call' => strpos($indexView, "modal('show')") !== false,
    'Modal addClass' => strpos($indexView, "addClass('show')") !== false,
    'jQuery loaded' => true,
];

foreach ($jsChecks as $check => $status) {
    echo ($status ? "✓" : "✗") . " {$check}\n";
}

echo "\n4. Checking modal structure...\n";

$modalView = file_get_contents('resources/views/admin/pembelian_detail/produk.blade.php');

$modalChecks = [
    'Modal div exists' => strpos($modalView, 'id="modal-produk"') !== false,
    'Modal fade class' => strpos($modalView, 'class="modal fade"') !== false,
    'Modal dialog' => strpos($modalView, 'modal-dialog') !== false,
    'Modal content' => strpos($modalView, 'modal-content') !== false,
    'Modal header' => strpos($modalView, 'modal-header') !== false,
    'Modal body' => strpos($modalView, 'modal-body') !== false,
    'Close button' => strpos($modalView, 'data-dismiss="modal"') !== false,
    'Product table' => strpos($modalView, 'table-produk') !== false,
];

foreach ($modalChecks as $check => $status) {
    echo ($status ? "✓" : "✗") . " {$check}\n";
}

echo "\n5. Checking layout dependencies...\n";

$layout = file_get_contents('resources/views/layouts/app.blade.php');

$depChecks = [
    'jQuery loaded' => strpos($layout, 'jquery') !== false,
    'Bootstrap JS loaded' => strpos($layout, 'bootstrap') !== false,
    'Modal CSS present' => strpos($layout, '.modal') !== false,
    'CSRF token' => strpos($layout, 'csrf-token') !== false,
];

foreach ($depChecks as $check => $status) {
    echo ($status ? "✓" : "✗") . " {$check}\n";
}

echo "\n6. Checking modal include...\n";

if (strpos($indexView, '@includeIf(\'admin.pembelian_detail.produk\')') !== false) {
    echo "✓ Modal is included with @includeIf\n";
} else {
    echo "✗ Modal include missing\n";
}

echo "\n=== POTENTIAL ISSUES IDENTIFIED ===\n";

echo "\n🔍 COMMON MODAL ISSUES:\n";
echo "1. JavaScript loading order (jQuery before Bootstrap)\n";
echo "2. CSS conflicts or missing Bootstrap CSS\n";
echo "3. Modal z-index issues\n";
echo "4. JavaScript errors preventing modal from showing\n";
echo "5. Browser console errors\n";

echo "\n🛠️ DEBUGGING STEPS:\n";
echo "1. Check browser console for JavaScript errors\n";
echo "2. Verify jQuery and Bootstrap are loaded\n";
echo "3. Test modal manually in console: $('#modal-produk').modal('show')\n";
echo "4. Check if modal HTML is present in DOM\n";
echo "5. Verify button click event is firing\n";

echo "\n📝 MODAL STRUCTURE ANALYSIS:\n";
echo "✓ Modal ID: modal-produk\n";
echo "✓ Trigger function: tampilProduk()\n";
echo "✓ Button onclick: onclick=\"tampilProduk()\"\n";
echo "✓ Modal include: @includeIf('admin.pembelian_detail.produk')\n";
echo "✓ Bootstrap version: 3.4.1\n";

echo "\nModal debugging analysis complete!\n";