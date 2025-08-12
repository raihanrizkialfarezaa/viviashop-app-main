<?php

require_once 'vendor/autoload.php';

echo "=== Debug Direct Shipping Implementation ===\n\n";

$origin = 'jombang';
$destination = 'surabaya'; 
$weight = 1;
$user_agent = "Googlebot/2.1 (http://www.googlebot.com/bot.html)";

echo "Testing TIKI shipping from $origin to $destination, weight: {$weight}kg\n";

// Test TIKI
$ch = curl_init();
$url = "http://www.tiki-online.com/?cat=KgfdshfF7788KHfskF";
$post = "&get_ori=$origin&get_des=$destination&get_wgdom=$weight&submit=Check";

curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$site = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "TIKI Response:\n";
echo "HTTP Code: $httpCode\n";
echo "Error: " . ($error ?: 'None') . "\n";
echo "Response length: " . strlen($site) . " bytes\n";

if ($httpCode === 200 && !empty($site)) {
    echo "Content preview: " . substr($site, 0, 500) . "...\n\n";
    
    // Look for table
    if (strpos($site, 'table') !== false) {
        echo "✓ Found table in TIKI response\n";
    } else {
        echo "✗ No table found in TIKI response\n";
    }
} else {
    echo "✗ TIKI request failed\n\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

echo "Testing JNE shipping from $origin to $destination\n";

// Test JNE origin
$json_dari = "http://www.jne.co.id/server/server_city_from.php?term=$origin";
echo "JNE Origin URL: $json_dari\n";

$json_daric = @file_get_contents($json_dari);
echo "Origin response: " . ($json_daric ?: 'Failed') . "\n";

if ($json_daric) {
    $hasil_dari = json_decode($json_daric);
    if ($hasil_dari && count($hasil_dari) > 0) {
        echo "Origin found: " . $hasil_dari[0]->label . " (Code: " . $hasil_dari[0]->code . ")\n";
        
        // Test destination
        $json_ke = "http://www.jne.co.id/server/server_city.php?term=$destination";
        echo "JNE Destination URL: $json_ke\n";
        
        $json_kec = @file_get_contents($json_ke);
        echo "Destination response: " . ($json_kec ?: 'Failed') . "\n";
        
        if ($json_kec) {
            $hasil_ke = json_decode($json_kec);
            if ($hasil_ke && count($hasil_ke) > 0) {
                echo "Destination found: " . $hasil_ke[0]->label . " (Code: " . $hasil_ke[0]->code . ")\n";
                
                // Test fare calculation
                $daric = $hasil_dari[0]->code;
                $darib = $hasil_dari[0]->label;
                $kec = $hasil_ke[0]->code;
                $keb = $hasil_ke[0]->label;
                
                $ch = curl_init();
                $url = "http://www.jne.co.id/getDetailFare.php";
                $post = "origin=$daric&dest=$kec&weight=$weight&originlabel=$darib&destlabel=$keb";
                
                echo "JNE Fare URL: $url\n";
                echo "JNE POST data: $post\n";
                
                curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                echo "JNE Fare Response:\n";
                echo "HTTP Code: $httpCode\n";
                echo "Error: " . ($error ?: 'None') . "\n";
                echo "Response: " . ($response ?: 'Empty') . "\n";
                
                if ($response) {
                    $data = json_decode($response, true);
                    if ($data && isset($data['data'])) {
                        echo "✓ JNE fare data found: " . count($data['data']) . " services\n";
                        foreach ($data['data'] as $service) {
                            echo "  - " . $service['service'] . ": Rp " . number_format($service['cost']) . " (" . $service['etd'] . ")\n";
                        }
                    } else {
                        echo "✗ No valid JNE fare data\n";
                    }
                }
            } else {
                echo "✗ JNE destination not found\n";
            }
        } else {
            echo "✗ Failed to get JNE destination data\n";
        }
    } else {
        echo "✗ JNE origin not found\n";
    }
} else {
    echo "✗ Failed to get JNE origin data\n";
}

echo "\n=== Debug Complete ===\n";
?>
