<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "📋 PRINT_ORDERS TABLE STRUCTURE\n";
echo "================================\n\n";

$columns = \DB::select('DESCRIBE print_orders');

foreach($columns as $col) {
    echo sprintf("%-20s | %-15s | %-8s | %-4s | %s\n", 
        $col->Field, 
        $col->Type, 
        $col->Null, 
        $col->Key, 
        $col->Default ?? 'NULL'
    );
}

echo "\n🔍 Status field details:\n";
$statusColumn = collect($columns)->firstWhere('Field', 'status');
if ($statusColumn) {
    echo "Type: {$statusColumn->Type}\n";
    echo "This explains the truncation error!\n\n";
}

echo "🎯 Valid status values should be:\n";
echo "- payment_pending\n";
echo "- confirmed  \n";
echo "- printing\n";
echo "- completed\n";
echo "- cancelled\n\n";

// Let's check what status values are currently in use
echo "📊 Current status values in database:\n";
$statuses = \DB::table('print_orders')->select('status', \DB::raw('COUNT(*) as count'))->groupBy('status')->get();
foreach($statuses as $status) {
    echo "- '{$status->status}' ({$status->count} orders)\n";
}

?>
