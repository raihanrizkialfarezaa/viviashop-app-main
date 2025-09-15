<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\SmartPrintVariantService;

echo "Testing Smart Print Variant Service...\n\n";

$service = new SmartPrintVariantService();

echo "Running auto-fix for existing variants...\n";
$results = $service->autoFixPrintServiceVariants();

echo "Auto-fix results:\n";
echo "Fixed: " . $results['fixed'] . " variants\n";
echo "Skipped: " . $results['skipped'] . " variants\n\n";

if(count($results['details']) > 0) {
    echo "Details:\n";
    foreach($results['details'] as $detail) {
        echo "- " . $detail . "\n";
    }
}

echo "\nDone!\n";