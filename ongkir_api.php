<?php

include_once 'rajaongkir_komerce.php';

$rajaOngkir = new RajaOngkirKomerce();

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_provinces':
            $result = $rajaOngkir->getProvinces();
            echo json_encode($result);
            break;
            
        case 'get_cities':
            if (isset($_GET['province_id'])) {
                $result = $rajaOngkir->getCities($_GET['province_id']);
                echo json_encode($result);
            } else {
                echo json_encode(['error' => 'Province ID required']);
            }
            break;
            
        case 'get_districts':
            if (isset($_GET['city_id'])) {
                $result = $rajaOngkir->getDistricts($_GET['city_id']);
                echo json_encode($result);
            } else {
                echo json_encode(['error' => 'City ID required']);
            }
            break;
            
        case 'calculate_shipping':
            if (isset($_GET['origin']) && isset($_GET['destination']) && isset($_GET['weight'])) {
                $courier = $_GET['courier'] ?? 'jne:tiki:pos';
                $result = $rajaOngkir->calculateShippingCost($_GET['origin'], $_GET['destination'], $_GET['weight'], $courier);
                echo json_encode($result);
            } else {
                echo json_encode(['error' => 'Origin, destination and weight required']);
            }
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}
?>
