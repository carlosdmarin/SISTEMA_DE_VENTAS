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

function llamarSupabase($endpoint) {
    global $supabase_url, $supabase_key;
    
    $ch = curl_init($supabase_url . '/rest/v1/' . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $supabase_key,
        'Authorization: Bearer ' . $supabase_key,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Para pruebas - devuelve todas las ventas (como test.php)
    if (isset($_GET['todas'])) {
        $result = llamarSupabase('ventas?select=*');
        echo json_encode($result ?: []);
        exit;
    }
    
    // Para ventas de hoy - usa el mismo método que test.php pero filtrando
    if (isset($_GET['hoy'])) {
        $todas = llamarSupabase('ventas?select=*');
        $hoy = date('Y-m-d');
        $filtradas = [];
        if (is_array($todas)) {
            foreach ($todas as $v) {
                $fecha_supabase = substr($v['fecha_registro'], 0, 10);
                if ($fecha_supabase === $hoy) {
                    $filtradas[] = $v;
                }
            }
        }
        echo json_encode($filtradas);
        exit;
    }
    
    // Para historial por rango de fechas
    if (isset($_GET['desde']) && isset($_GET['hasta'])) {
        $todas = llamarSupabase('ventas?select=*');
        $desde = $_GET['desde'];
        $hasta = $_GET['hasta'];
        $filtradas = [];
        if (is_array($todas)) {
            foreach ($todas as $v) {
                $fecha_supabase = substr($v['fecha_registro'], 0, 10);
                if ($fecha_supabase >= $desde && $fecha_supabase <= $hasta) {
                    $filtradas[] = $v;
                }
            }
        }
        echo json_encode($filtradas);
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
    
    $ch = curl_init($supabase_url . '/rest/v1/ventas');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $supabase_key,
        'Authorization: Bearer ' . $supabase_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($venta));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'PUT') {
    $path = $_SERVER['PATH_INFO'] ?? '';
    if (preg_match('/^\/(\d+)\/pagar$/', $path, $matches)) {
        $id = $matches[1];
        
        $ch = curl_init($supabase_url . '/rest/v1/ventas?id_venta=eq.' . $id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $supabase_key,
            'Authorization: Bearer ' . $supabase_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['estado' => 'cancelado']));
        
        curl_exec($ch);
        curl_close($ch);
        
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['error' => 'Método no permitido']);
?>
