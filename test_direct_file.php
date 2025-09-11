<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 DIRECT FILE CREATION TEST\n";
echo "=============================\n\n";

$date = '2025-09-11';
$sessionToken = 'direct-test-' . time();
$testDir = storage_path("app/print-files/{$date}/{$sessionToken}");

echo "1️⃣ Creating directory manually: {$testDir}\n";
if (!is_dir($testDir)) {
    $result = mkdir($testDir, 0755, true);
    echo "Directory creation: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
} else {
    echo "Directory already exists\n";
}

echo "\n2️⃣ Writing file directly...\n";
$testFile = $testDir . '/direct_test.txt';
$content = "Direct file write test at " . date('Y-m-d H:i:s');
$result = file_put_contents($testFile, $content);
echo "File write result: " . ($result !== false ? "SUCCESS ({$result} bytes)" : 'FAILED') . "\n";

echo "\n3️⃣ Verifying file...\n";
if (file_exists($testFile)) {
    echo "File exists: YES\n";
    echo "File size: " . filesize($testFile) . " bytes\n";
    echo "Content: " . file_get_contents($testFile) . "\n";
} else {
    echo "File exists: NO\n";
}

?>
