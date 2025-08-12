<?php
echo "=== DIRECT API TEST ===\n\n";

// Test Mojokerto districts directly via API
$apiUrl = 'https://rajaongkir.komerce.id/api/districts?city_id=388';

echo "Testing API: " . $apiUrl . "\n";
echo "Expected: Mojokerto districts (Bangsal, Dawar Blandong, etc.)\n\n";

try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($response && $httpCode == 200) {
        $data = json_decode($response, true);
        
        if ($data && isset($data['data'])) {
            $districts = $data['data'];
            echo "Districts found: " . count($districts) . "\n";
            
            // Check for ALAK (wrong) and BANGSAL (correct)
            $hasAlak = false;
            $hasBangsal = false;
            $sampleDistricts = [];
            
            foreach($districts as $district) {
                if(strpos(strtoupper($district['name']), 'ALAK') !== false) {
                    $hasAlak = true;
                }
                if(strpos(strtoupper($district['name']), 'BANGSAL') !== false) {
                    $hasBangsal = true;
                }
                
                // Store first 10 districts as sample
                if(count($sampleDistricts) < 10) {
                    $sampleDistricts[] = $district['name'];
                }
            }
            
            echo "Sample districts: " . implode(', ', $sampleDistricts) . "\n\n";
            echo "Has ALAK (wrong): " . ($hasAlak ? 'YES' : 'NO') . "\n";
            echo "Has BANGSAL (correct): " . ($hasBangsal ? 'YES' : 'NO') . "\n\n";
            
            if(!$hasAlak && $hasBangsal) {
                echo "✅ CORRECT! This is Mojokerto data\n";
            } else {
                echo "❌ WRONG! This is NOT Mojokerto data\n";
            }
            
        } else {
            echo "❌ Invalid response format\n";
            echo "Response: " . $response . "\n";
        }
    } else {
        echo "❌ API Error: HTTP " . $httpCode . "\n";
        echo "Response: " . $response . "\n";
    }
    
    curl_close($ch);
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== END TEST ===\n";
?>
