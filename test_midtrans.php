<?php

echo "Testing Midtrans Configuration:\n";
echo "Server Key: " . (config('midtrans.serverKey') ? 'Set' : 'Not Set') . "\n";
echo "Client Key: " . (config('midtrans.clientKey') ? 'Set' : 'Not Set') . "\n";
echo "Is Production: " . (config('midtrans.isProduction') ? 'true' : 'false') . "\n";

// Test Midtrans Config
try {
    \Midtrans\Config::$serverKey = config('midtrans.serverKey');
    \Midtrans\Config::$isProduction = config('midtrans.isProduction');
    echo "Midtrans Config set successfully\n";
    echo "Server Key in Midtrans Config: " . (\Midtrans\Config::$serverKey ? 'Set' : 'Not Set') . "\n";
} catch (Exception $e) {
    echo "Error setting Midtrans Config: " . $e->getMessage() . "\n";
}
