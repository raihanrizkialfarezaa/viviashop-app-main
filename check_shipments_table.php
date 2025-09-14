<?php

require_once './vendor/autoload.php';
$app = require_once './bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== SHIPMENTS TABLE STRUCTURE ===\n\n";

$columns = DB::select('SHOW COLUMNS FROM shipments');
foreach($columns as $col) {
    echo $col->Field . ' - ' . $col->Type . ' - NULL:' . $col->Null . ' - Default:' . ($col->Default ?? 'NONE') . "\n";
}

echo "\n=== CHECKING IF STATUS COLUMN EXISTS ===\n";
$hasStatus = false;
foreach($columns as $col) {
    if ($col->Field === 'status') {
        $hasStatus = true;
        echo "✓ Status column found: " . $col->Type . " - Default: " . ($col->Default ?? 'NONE') . "\n";
        break;
    }
}

if (!$hasStatus) {
    echo "❌ Status column NOT found\n";
}

echo "\n=== COMPLETE ===\n";