<?php

require_once 'vendor/autoload.php';

/*********************************************************************
Script untuk mengambil ongkir Tiki dan JNE
langsung melalui web tiki-online.com dan jne.co.id
dengan menggunakan cURL dan simple html dom.

Based on: https://github.com/bachors/cURL-Cek-Ongkir-TIKI-dan-JNE
*********************************************************************/

/**
 * Get TIKI shipping cost
 */
function getTikiShippingCost($dari, $ke, $kg, $user_agent = "Googlebot/2.1 (http://www.googlebot.com/bot.html)") {
    $ch = curl_init();
    $url = "http://www.tiki-online.com/?cat=KgfdshfF7788KHfskF";
    $post = "&get_ori=$dari&get_des=$ke&get_wgdom=$kg&submit=Check";
    
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    
    $site = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['error' => 'TIKI service unavailable'];
    }
    
    return parseTikiResponse($site);
}

/**
 * Parse TIKI HTML response
 */
function parseTikiResponse($html) {
    $dom = new \voku\helper\HtmlDomParser();
    $dom->loadHtml($html);
    
    $table = $dom->findOne('table[cellpadding="4"]');
    if (!$table) {
        return ['error' => 'No TIKI shipping options found'];
    }
    
    $results = [];
    $rows = $table->find('tr');
    
    foreach ($rows as $row) {
        $cells = $row->find('td');
        $cellsArray = [];
        
        // Convert to array for easier processing
        foreach ($cells as $cell) {
            $cellsArray[] = $cell;
        }
        
        if (count($cellsArray) >= 3) {
            $service = trim($cellsArray[0]->text());
            $etd = trim($cellsArray[1]->text());
            $cost = trim($cellsArray[2]->text());
            
            // Extract numeric cost
            $cost = preg_replace('/[^0-9]/', '', $cost);
            
            if (!empty($service) && !empty($cost) && is_numeric($cost)) {
                $results[] = [
                    'service' => 'TIKI - ' . $service,
                    'cost' => (int)$cost,
                    'etd' => $etd,
                    'courier' => 'tiki'
                ];
            }
        }
    }
    
    return $results;
}

/**
 * Get JNE shipping cost
 */
function getJneShippingCost($dari, $ke, $kg, $user_agent = "Googlebot/2.1 (http://www.googlebot.com/bot.html)") {
    // Get origin city data
    $json_dari = "http://www.jne.co.id/server/server_city_from.php?term=$dari";
    $json_daric = file_get_contents($json_dari);
    $hasil_dari = json_decode($json_daric);
    
    // Get destination city data
    $json_ke = "http://www.jne.co.id/server/server_city.php?term=$ke";
    $json_kec = file_get_contents($json_ke);
    $hasil_ke = json_decode($json_kec);
    
    if ($hasil_dari == null || $hasil_ke == null) {
        return ['error' => 'JNE city not found'];
    }
    
    $daric = $hasil_dari[0]->code;
    $darib = $hasil_dari[0]->label;
    $kec = $hasil_ke[0]->code;
    $keb = $hasil_ke[0]->label;
    
    $ch = curl_init();
    $url = "http://www.jne.co.id/getDetailFare.php";
    $post = "origin=$daric&dest=$kec&weight=$kg&originlabel=$darib&destlabel=$keb";
    
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['error' => 'JNE service unavailable'];
    }
    
    return parseJneResponse($response);
}

/**
 * Parse JNE response
 */
function parseJneResponse($response) {
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['data'])) {
        return ['error' => 'Invalid JNE response'];
    }
    
    $results = [];
    foreach ($data['data'] as $service) {
        $results[] = [
            'service' => 'JNE - ' . $service['service'],
            'cost' => (int)$service['cost'],
            'etd' => $service['etd'],
            'courier' => 'jne'
        ];
    }
    
    return $results;
}

/**
 * Get shipping costs from multiple couriers
 */
function getShippingCosts($origin, $destination, $weight) {
    $results = [];
    $user_agent = "Googlebot/2.1 (http://www.googlebot.com/bot.html)";
    
    // Get TIKI costs
    try {
        $tikiResults = getTikiShippingCost($origin, $destination, $weight, $user_agent);
        if (is_array($tikiResults) && !isset($tikiResults['error'])) {
            $results = array_merge($results, $tikiResults);
        }
    } catch (Exception $e) {
        // Continue with other couriers if TIKI fails
    }
    
    // Get JNE costs
    try {
        $jneResults = getJneShippingCost($origin, $destination, $weight, $user_agent);
        if (is_array($jneResults) && !isset($jneResults['error'])) {
            $results = array_merge($results, $jneResults);
        }
    } catch (Exception $e) {
        // Continue if JNE fails
    }
    
    return $results;
}

// Test the implementation
if (php_sapi_name() === 'cli') {
    echo "=== Testing Direct Shipping Cost Implementation ===\n\n";
    
    $origin = 'jombang';
    $destination = 'surabaya'; 
    $weight = 1; // 1 kg
    
    echo "Testing shipping from $origin to $destination, weight: {$weight}kg\n\n";
    
    $results = getShippingCosts($origin, $destination, $weight);
    
    if (empty($results)) {
        echo "No shipping options found\n";
    } else {
        echo "Found " . count($results) . " shipping options:\n\n";
        foreach ($results as $result) {
            echo "- {$result['service']}: Rp " . number_format($result['cost']) . " ({$result['etd']})\n";
        }
    }
}
?>
