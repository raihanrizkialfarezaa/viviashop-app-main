<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== STRUKTUR TABEL PRODUCTS ===\n\n";

$columns = DB::select('DESCRIBE products');
foreach ($columns as $col) {
    echo "{$col->Field} - {$col->Type} - NULL:{$col->Null} - Default:{$col->Default}\n";
}