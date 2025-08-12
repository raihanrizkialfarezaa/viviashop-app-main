<?php

class OngkirAppShipping
{
    private $baseUrl = 'http://cekongkir.mlopp.com/api/';
    
    public function getProvinces()
    {
        $url = $this->baseUrl . 'province';
        return $this->makeRequest($url, 'GET');
    }
    
    public function getCities($provinceId = null)
    {
        $url = $this->baseUrl . 'city';
        if ($provinceId) {
            $url .= '?province=' . $provinceId;
        }
        return $this->makeRequest($url, 'GET');
    }
    
    public function getSubdistricts($cityId)
    {
        $url = $this->baseUrl . 'subdistrict?city=' . $cityId;
        return $this->makeRequest($url, 'GET');
    }
    
    public function getShippingCost($origin, $originType, $destination, $destinationType, $weight, $courier)
    {
        $url = $this->baseUrl . 'cost';
        $data = [
            'origin' => $origin,
            'originType' => $originType,
            'destination' => $destination,
            'destinationType' => $destinationType,
            'weight' => $weight,
            'courier' => $courier
        ];
        return $this->makeRequest($url, 'POST', $data);
    }
    
    public function trackPackage($waybill, $courier)
    {
        $url = $this->baseUrl . 'waybill';
        $data = [
            'waybill' => $waybill,
            'courier' => $courier
        ];
        return $this->makeRequest($url, 'POST', $data);
    }
    
    private function makeRequest($url, $method = 'GET', $data = null)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            curl_close($ch);
            return [
                'error' => true,
                'message' => 'cURL Error: ' . curl_error($ch)
            ];
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return [
                'error' => true,
                'message' => 'HTTP Error: ' . $httpCode,
                'response' => $response
            ];
        }
        
        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'error' => true,
                'message' => 'JSON Decode Error: ' . json_last_error_msg(),
                'raw_response' => $response
            ];
        }
        
        return $decodedResponse;
    }
}

function getOngkirAppShippingCosts($origin, $originType, $destination, $destinationType, $weight, $courier = 'jne')
{
    $ongkir = new OngkirAppShipping();
    $result = $ongkir->getShippingCost($origin, $originType, $destination, $destinationType, $weight, $courier);
    
    if (isset($result['error'])) {
        return [];
    }
    
    if (!isset($result['rajaongkir']['results'])) {
        return [];
    }
    
    $shippingOptions = [];
    
    foreach ($result['rajaongkir']['results'] as $courier) {
        if (isset($courier['costs'])) {
            foreach ($courier['costs'] as $service) {
                if (isset($service['cost'][0])) {
                    $shippingOptions[] = [
                        'courier' => $courier['code'],
                        'courier_name' => $courier['name'],
                        'service' => $service['service'],
                        'description' => $service['description'],
                        'cost' => $service['cost'][0]['value'],
                        'etd' => $service['cost'][0]['etd']
                    ];
                }
            }
        }
    }
    
    return $shippingOptions;
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $origin = '501';
    $originType = 'city';
    $destination = '574';
    $destinationType = 'subdistrict';
    $weight = 1000;
    $courier = 'jne';
    
    echo "Testing OngkirApp shipping cost calculation...\n\n";
    
    $costs = getOngkirAppShippingCosts($origin, $originType, $destination, $destinationType, $weight, $courier);
    
    if (empty($costs)) {
        echo "No shipping options found.\n";
    } else {
        echo "Shipping options found:\n";
        foreach ($costs as $option) {
            echo "- {$option['courier_name']} ({$option['service']}): Rp " . number_format($option['cost']) . " ({$option['etd']} hari)\n";
        }
    }
}
