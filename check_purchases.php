<?php
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== LATEST PURCHASES ===\n";
$purchases = \App\Models\Pembelian::latest()->take(5)->get(['id', 'total_item', 'created_at']);
foreach ($purchases as $p) {
    echo "ID: {$p->id}, Items: {$p->total_item}, Date: {$p->created_at}\n";
}

echo "\n=== LATEST PURCHASE DETAILS ===\n";
$details = \App\Models\PembelianDetail::with('product')->latest()->take(3)->get();
foreach ($details as $d) {
    $productName = $d->product ? $d->product->name : 'N/A';
    echo "Detail ID: {$d->id}, Purchase ID: {$d->id_pembelian}, Product: {$productName}\n";
}