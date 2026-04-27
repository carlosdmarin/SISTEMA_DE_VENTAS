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

function supabase($endpoint, $method = 'GET', $data = null) {
    global $supabase_url, $supabase_key;
    
    $url = $supabase_url . '/rest/v1/' . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $supabase_key,
        'Authorization: Bearer ' . $supabase_key,
        'Content-Type: application/json'
    ]);
    
    if ($data && ($method === 'POST' || $method === 'PUT')) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    }
    return ['error' => "HTTP $httpCode", 'detail' => $response];
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['hoy'])) {
        $hoy = date('Y-m-d');
        $result = supabase("ventas?select=*&fecha_registro=gte." . $hoy . "&fecha_registro=lt." . date('Y-m-d', strtotime('+1 day')));
        echo json_encode($result ?: []);
        exit;
    }
    if (isset($_GET['desde']) && isset($_GET['hasta'])) {
        $result = supabase("ventas?select=*&fecha_registro=gte." . $_GET['desde'] . "&fecha_registro=lte." . $_GET['hasta']);
        echo json_encode($result ?: []);
        exit;
    }
    echo json_encode([]);
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
    $result = supabase("ventas", "POST", $venta);
    echo json_encode(['success' => true, 'id' => $result[0]['id_venta'] ?? null]);
    exit;
}

if ($method === 'PUT') {
    $path = $_SERVER['PATH_INFO'] ?? '';
    if (preg_match('/^\/(\d+)\/pagar$/', $path, $matches)) {
        // CORREGIDO: 'cancelado' en lugar de 'pagado'
        $result = supabase("ventas?id_venta=eq." . $matches[1], "PUT", ['estado' => 'cancelado']);
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['error' => 'Método no permitido']);
?>
