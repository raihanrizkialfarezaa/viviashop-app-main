<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "🔍 CHECKING TABLE SCHEMAS\n";
echo "=========================\n\n";

echo "📋 print_orders columns:\n";
$orderColumns = Schema::getColumnListing('print_orders');
foreach ($orderColumns as $column) {
    echo "  - {$column}\n";
}

echo "\n📋 print_files columns:\n";
$fileColumns = Schema::getColumnListing('print_files');
foreach ($fileColumns as $column) {
    echo "  - {$column}\n";
}

echo "\n📋 print_sessions columns:\n";
$sessionColumns = Schema::getColumnListing('print_sessions');
foreach ($sessionColumns as $column) {
    echo "  - {$column}\n";
}

echo "\n✅ Schema check complete!\n";
