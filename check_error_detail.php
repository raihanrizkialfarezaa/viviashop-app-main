<?php

echo "=== DETAILED ERROR CHECK ===\n";

$url = 'http://127.0.0.1:8000/shop/detail/4';
$context = stream_context_create(['http' => ['timeout' => 10]]);
$response = file_get_contents($url, false, $context);

// Extract specific errors
if (strpos($response, 'Error') !== false) {
    echo "Found 'Error' in response\n";
    
    // Find context around the error
    $errorPos = strpos($response, 'Error');
    $start = max(0, $errorPos - 100);
    $end = min(strlen($response), $errorPos + 200);
    $context = substr($response, $start, $end - $start);
    echo "Context: " . htmlspecialchars($context) . "\n";
}

if (strpos($response, 'Exception') !== false) {
    echo "Found 'Exception' in response\n";
    
    // Find context around the exception
    $exceptionPos = strpos($response, 'Exception');
    $start = max(0, $exceptionPos - 100);
    $end = min(strlen($response), $exceptionPos + 200);
    $context = substr($response, $start, $end - $start);
    echo "Context: " . htmlspecialchars($context) . "\n";
}

echo "\nChecking if it's just CSS/JS class names...\n";
if (strpos($response, 'btn-primary') !== false) {
    echo "✓ Contains 'btn-primary' (CSS class - not an error)\n";
}

if (strpos($response, 'form-control') !== false) {
    echo "✓ Contains 'form-control' (CSS class - not an error)\n";
}

if (strpos($response, 'Tambah ke Keranjang') !== false) {
    echo "✓ Contains 'Tambah ke Keranjang' button\n";
}

echo "\nDone.\n";
