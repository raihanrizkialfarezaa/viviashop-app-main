<?php

$apiKey = "Ho0D8T1Ebf59683c23db234aV2uzrSn6";

$response = file_get_contents("https://rajaongkir.komerce.id/api/v1/province", false, stream_context_create([
    'http' => [
        'header' => "key: $apiKey\r\n"
    ]
]));
$provinces = json_decode($response, true);

$jawaTimur = null;
foreach ($provinces['data'] as $province) {
    if ($province['province'] == "Jawa Timur") {
        $jawaTimur = $province;
        break;
    }
}

if ($jawaTimur) {
    $idJawaTimur = $jawaTimur['province_id'];
    echo "ID Provinsi Jawa Timur: " . $idJawaTimur . "\n";

    $responseKota = file_get_contents("https://rajaongkir.komerce.id/api/v1/city", false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "key: $apiKey\r\nContent-Type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query(['province' => $idJawaTimur])
        ]
    ]));
    $cities = json_decode($responseKota, true);

    $jombang = null;
    foreach ($cities['data'] as $city) {
        if ($city['city_name'] == "Jombang") {
            $jombang = $city;
            break;
        }
    }

    if ($jombang) {
        $idJombang = $jombang['city_id'];
        echo "ID Kabupaten Jombang: " . $idJombang . "\n";
    } else {
        echo "Kabupaten Jombang tidak ditemukan.\n";
    }

} else {
    echo "Provinsi Jawa Timur tidak ditemukan.\n";
}

?>
