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
    } elseif ($method === 'PATCH') {  // CAMBIADO: Usar PATCH en lugar de PUT
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
    
    return ['success' => $httpCode >= 200 && $httpCode < 300, 'http_code' => $httpCode];
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
            // Extraer solo la fecha YYYY-MM-DD del campo fecha_registro
            $fecha_venta = substr($v['fecha_registro'], 0, 10);
            if ($fecha_venta >= $desde && $fecha_venta <= $hasta) {
                $filtradas[] = $v;
            }
        }
        
        echo json_encode($filtradas);
    } 
    // Si quieren todas
    else {
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

if ($method === 'PUT') {
    // Obtener el path de diferentes maneras posibles
    $path = null;
    
    // Intentar obtener PATH_INFO
    if (isset($_SERVER['PATH_INFO'])) {
        $path = $_SERVER['PATH_INFO'];
    }
    // Si no, intentar con REQUEST_URI
    else if (isset($_SERVER['REQUEST_URI'])) {
        $uri = $_SERVER['REQUEST_URI'];
        // Remover el script name de la URI
        $script = $_SERVER['SCRIPT_NAME'];
        $path = str_replace($script, '', $uri);
        // Remover query string si existe
        $path = strtok($path, '?');
    }
    
    error_log("Path recibido: " . $path); // Debug
    
    if ($path && preg_match('/^\/(\d+)\/pagar$/', $path, $matches)) {
        $id = $matches[1];
        error_log("Actualizando venta ID: " . $id); // Debug
        
        // Usar PATCH en lugar de PUT
        $result = llamarSupabase('ventas?id_venta=eq.' . $id, 'PATCH', ['estado' => 'cancelado']);
        
        if ($result['success']) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al actualizar en Supabase', 'http_code' => $result['http_code']]);
        }
        exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Ruta no válida', 'path' => $path]);
    exit;
}

echo json_encode(['error' => 'Método no permitido']);
?>
