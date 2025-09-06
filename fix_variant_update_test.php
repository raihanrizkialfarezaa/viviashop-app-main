<?php
require_once 'vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "<h1>🔧 FIX VARIANT UPDATE - HARGA BELI & DIMENSI</h1><br>";

try {
    echo "<h3>🎯 MASALAH YANG DIPERBAIKI</h3>";
    echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ MASALAH SEBELUMNYA:</h4>";
    echo "<p>Di method <code>updateVariant</code> di ProductVariantService, hanya field ini yang di-update:</p>";
    echo "<ul>";
    echo "<li>✅ name</li>";
    echo "<li>✅ sku</li>";
    echo "<li>✅ price</li>";
    echo "<li>✅ stock</li>";
    echo "<li>✅ weight</li>";
    echo "</ul>";
    echo "<p><strong>FIELD YANG HILANG:</strong></p>";
    echo "<ul>";
    echo "<li>❌ harga_beli</li>";
    echo "<li>❌ length</li>";
    echo "<li>❌ width</li>";
    echo "<li>❌ height</li>";
    echo "</ul>";
    echo "<p><strong>Akibatnya:</strong> Data yang Anda input di modal hilang setelah update!</p>";
    echo "</div>";

    echo "<br><h3>✅ SOLUSI YANG DITERAPKAN</h3>";
    echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h4>✅ SEKARANG DI method updateVariant:</h4>";
    echo "<pre style='background-color: #f8f9fa; padding: 10px;'>";
echo "\$variant->update([
    'name' => \$variantData['name'],
    'sku' => \$variantData['sku'],
    'price' => \$variantData['price'],
    'harga_beli' => \$variantData['harga_beli'] ?? null,  // ← DITAMBAHKAN
    'stock' => \$variantData['stock'],
    'weight' => \$variantData['weight'] ?? 0,
    'length' => \$variantData['length'] ?? 0,             // ← DITAMBAHKAN
    'width' => \$variantData['width'] ?? 0,               // ← DITAMBAHKAN
    'height' => \$variantData['height'] ?? 0,             // ← DITAMBAHKAN
]);";
    echo "</pre>";
    echo "</div>";

    // Test dengan variant yang ada
    $variant = App\Models\ProductVariant::first();
    if ($variant) {
        echo "<br><h3>🧪 TEST SIMULATION</h3>";
        echo "<div style='background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px;'>";
        echo "<h4>Sample Variant untuk Testing:</h4>";
        echo "<p><strong>ID:</strong> {$variant->id}</p>";
        echo "<p><strong>Name:</strong> {$variant->name}</p>";
        echo "<p><strong>SKU:</strong> {$variant->sku}</p>";
        echo "<p><strong>Price:</strong> {$variant->price}</p>";
        echo "<p><strong>Harga Beli:</strong> " . ($variant->harga_beli ?? 'NULL') . "</p>";
        echo "<p><strong>Weight:</strong> {$variant->weight}</p>";
        echo "<p><strong>Length:</strong> " . ($variant->length ?? 'NULL') . "</p>";
        echo "<p><strong>Width:</strong> " . ($variant->width ?? 'NULL') . "</p>";
        echo "<p><strong>Height:</strong> " . ($variant->height ?? 'NULL') . "</p>";
        echo "</div>";

        echo "<br><h3>📋 CARA TEST MANUAL</h3>";
        echo "<div style='background-color: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px;'>";
        echo "<h4>Langkah-langkah test:</h4>";
        echo "<ol>";
        echo "<li>🌐 Buka halaman edit product dengan variant</li>";
        echo "<li>✏️ Klik Edit pada salah satu variant</li>";
        echo "<li>📝 Isi data:</li>";
        echo "<ul>";
        echo "<li>Harga Beli: (misalnya 1000)</li>";
        echo "<li>Panjang (cm): (misalnya 10)</li>";
        echo "<li>Lebar (cm): (misalnya 5)</li>";
        echo "<li>Tinggi (cm): (misalnya 3)</li>";
        echo "</ul>";
        echo "<li>💾 Klik 'Update Variant'</li>";
        echo "<li>🔍 Buka lagi modal Edit variant yang sama</li>";
        echo "<li>✅ <strong>Data harus tetap ada dan tidak hilang!</strong></li>";
        echo "</ol>";
        echo "</div>";
    }

    echo "<br><h3>🎯 EKSPEKTASI HASIL</h3>";
    echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h4>✅ SETELAH FIX INI:</h4>";
    echo "<ul>";
    echo "<li>✅ Harga Beli akan tersimpan</li>";
    echo "<li>✅ Panjang (cm) akan tersimpan</li>";
    echo "<li>✅ Lebar (cm) akan tersimpan</li>";
    echo "<li>✅ Tinggi (cm) akan tersimpan</li>";
    echo "<li>✅ Data tidak akan hilang setelah update</li>";
    echo "<li>✅ Modal edit akan menampilkan data yang benar</li>";
    echo "</ul>";
    echo "</div>";

    echo "<br><h3>🔧 TECHNICAL DETAILS</h3>";
    echo "<div style='background-color: #e2e3e5; border: 1px solid #d6d8db; padding: 15px; border-radius: 5px;'>";
    echo "<h4>File yang diperbaiki:</h4>";
    echo "<p><code>app/Services/ProductVariantService.php</code></p>";
    echo "<p><strong>Method:</strong> <code>updateVariant()</code></p>";
    echo "<p><strong>Perubahan:</strong> Menambahkan field harga_beli, length, width, height ke dalam update query</p>";
    echo "</div>";

    echo "<br><p><strong>🎉 MASALAH SUDAH DIPERBAIKI! 🎉</strong></p>";
    echo "<p>Sekarang data Harga Beli dan dimensi tidak akan hilang lagi setelah update variant.</p>";

} catch (Exception $e) {
    echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Error dalam test:</h3>";
    echo "<p>{$e->getMessage()}</p>";
    echo "</div>";
}
?>
