<?php

class RapidAPIShipping
{
    private $apiKey = '38e8351dc6msh1abf4d7f2207051p129c6bjsn2f5ac5ee1839';
    private $baseUrl = 'https://cek-resi-cek-ongkir.p.rapidapi.com/';
    
    public function getShippingCost($originAreaId, $destinationAreaId)
    {
        $url = $this->baseUrl . 'shipping-cost?originAreaId=' . $originAreaId . '&destinationAreaId=' . $destinationAreaId;
        return $this->makeRequest($url);
    }
    
    public function getProvinces()
    {
        $url = $this->baseUrl . 'province';
        return $this->makeRequest($url);
    }
    
    public function getCities($provinceId = null)
    {
        $url = $this->baseUrl . 'city';
        if ($provinceId) {
            $url .= '?province=' . $provinceId;
        }
        return $this->makeRequest($url);
    }
    
    public function getSubdistricts($cityId)
    {
        $url = $this->baseUrl . 'subdistrict?city=' . $cityId;
        return $this->makeRequest($url);
    }
    
    public function trackPackage($waybill, $courier)
    {
        $url = $this->baseUrl . 'waybill';
        $data = [
            'waybill' => $waybill,
            'courier' => $courier
        ];
        return $this->makePostRequest($url, $data);
    }
    
    private function makeRequest($url)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-rapidapi-key: ' . $this->apiKey,
            'x-rapidapi-host: cek-resi-cek-ongkir.p.rapidapi.com',
            'Accept: application/json'
        ]);
        
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
    
    private function makePostRequest($url, $data)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'x-rapidapi-key: ' . $this->apiKey,
            'x-rapidapi-host: cek-resi-cek-ongkir.p.rapidapi.com',
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
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

function getRapidAPIShippingCosts($originAreaId, $destinationAreaId)
{
    $rapidAPI = new RapidAPIShipping();
    $result = $rapidAPI->getShippingCost($originAreaId, $destinationAreaId);
    
    if (isset($result['error'])) {
        return [];
    }
    
    if (!isset($result['data']) || !is_array($result['data'])) {
        return [];
    }
    
    $shippingOptions = [];
    
    foreach ($result['data'] as $courier) {
        if (isset($courier['services']) && is_array($courier['services'])) {
            foreach ($courier['services'] as $service) {
                $shippingOptions[] = [
                    'courier' => $courier['courier_code'] ?? $courier['code'] ?? 'unknown',
                    'courier_name' => $courier['courier_name'] ?? $courier['name'] ?? 'Unknown Courier',
                    'service' => $service['service'] ?? $service['service_name'] ?? 'Unknown Service',
                    'description' => $service['description'] ?? '',
                    'cost' => $service['cost'] ?? $service['price'] ?? 0,
                    'etd' => $service['etd'] ?? $service['estimation'] ?? ''
                ];
            }
        } else {
            $shippingOptions[] = [
                'courier' => $courier['courier_code'] ?? $courier['code'] ?? 'unknown',
                'courier_name' => $courier['courier_name'] ?? $courier['name'] ?? 'Unknown Courier',
                'service' => $courier['service'] ?? 'Regular',
                'description' => $courier['description'] ?? '',
                'cost' => $courier['cost'] ?? $courier['price'] ?? 0,
                'etd' => $courier['etd'] ?? $courier['estimation'] ?? ''
            ];
        }
    }
    
    return $shippingOptions;
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $originAreaId = '4616';
    $destinationAreaId = '685';
    
    echo "Testing RapidAPI shipping cost calculation...\n\n";
    
    $costs = getRapidAPIShippingCosts($originAreaId, $destinationAreaId);
    
    if (empty($costs)) {
        echo "No shipping options found.\n";
        
        $rapidAPI = new RapidAPIShipping();
        $rawResult = $rapidAPI->getShippingCost($originAreaId, $destinationAreaId);
        echo "Raw API response:\n";
        print_r($rawResult);
    } else {
        echo "Shipping options found:\n";
        foreach ($costs as $option) {
            echo "- {$option['courier_name']} ({$option['service']}): Rp " . number_format($option['cost']) . " ({$option['etd']} hari)\n";
        }
    }
}
