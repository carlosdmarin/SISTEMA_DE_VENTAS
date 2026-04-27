<?php
date_default_timezone_set('America/Lima');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$supabase_url = 'https://ownjmawswuygfhltlzts.supabase.co';
$supabase_key = 'sb_publishable_5ceuA5WElQ_dB31Oddj1bg_Pa-7uFZz';

function llamarSupabase($endpoint, $method = 'GET', $data = null) {
    global $supabase_url, $supabase_key;
    
    $ch = curl_init($supabase_url . '/rest/v1/' . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $headers = [
        'apikey: ' . $supabase_key,
        'Authorization: Bearer ' . $supabase_key,
        'Content-Type: application/json'
    ];
    
    if ($method === 'GET') {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    } elseif ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($method === 'GET') {
        return json_decode($response, true);
    }
    
    return ['success' => $httpCode >= 200 && $httpCode < 300];
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Obtener todas las ventas
    $todas = llamarSupabase('ventas?select=*&order=fecha_registro.desc');
    
    if (!is_array($todas)) {
        echo json_encode([]);
        exit;
    }
    
    // Si hay filtro por rango de fechas
    if (isset($_GET['desde']) && isset($_GET['hasta'])) {
        $desde = $_GET['desde'];
        $hasta = $_GET['hasta'];
        $filtradas = [];
        
        foreach ($todas as $v) {
            $fecha_venta = substr($v['fecha_registro'], 0, 10);
            if ($fecha_venta >= $desde && $fecha_venta <= $hasta) {
                $filtradas[] = $v;
            }
        }
        
        echo json_encode($filtradas);
    } else {
        echo json_encode($todas);
    }
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $venta = [
        'nombre_cliente' => $input['nombreCliente'],
        'kilos' => floatval($input['kilos']),
        'total' => floatval($input['total']),
        'estado' => $input['estado'],
        'fecha_registro' => date('Y-m-d H:i:s')
    ];
    
    $result = llamarSupabase('ventas', 'POST', $venta);
    echo json_encode(['success' => $result['success']]);
    exit;
}

// CAMBIADO: Recibir el ID por query string en lugar de PATH_INFO
if ($method === 'PUT') {
    if (isset($_GET['pagar'])) {
        $id = $_GET['pagar'];
        
        // Actualizar el estado en Supabase usando PATCH
        $result = llamarSupabase('ventas?id_venta=eq.' . $id, 'PATCH', ['estado' => 'cancelado']);
        
        if ($result['success']) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error en Supabase']);
        }
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
    exit;
}

echo json_encode(['error' => 'Método no permitido']);
?>
