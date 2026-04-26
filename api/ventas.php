<?php
date_default_timezone_set('America/Lima');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$supabase_url = getenv('SUPABASE_URL');
$supabase_key = getenv('SUPABASE_KEY');

if (!$supabase_url || !$supabase_key) {
    echo json_encode(["error" => "Faltan variables SUPABASE_URL o SUPABASE_KEY"]);
    exit;
}

function supabase($endpoint, $method = 'GET', $data = null) {
    global $supabase_url, $supabase_key;
    $ch = curl_init("$supabase_url/rest/v1/$endpoint");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $supabase_key",
        "Authorization: Bearer $supabase_key",
        "Content-Type: application/json"
    ]);
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if (isset($_GET['hoy'])) {
        $hoy = date('Y-m-d');
        // ✅ CORREGIDO: usa punto (.) en lugar de = después de gte
        $result = supabase("ventas?fecha_registro=gte." . $hoy . "&fecha_registro=lt." . date('Y-m-d', strtotime('+1 day')));
        echo json_encode($result ?: []);
    } 
    elseif (isset($_GET['desde']) && isset($_GET['hasta'])) {
        $desde = $_GET['desde'];
        $hasta = $_GET['hasta'];
        // ✅ CORREGIDO: mismo formato
        $result = supabase("ventas?fecha_registro=gte." . $desde . "&fecha_registro=lte." . $hasta);
        echo json_encode($result ?: []);
    } 
    else {
        echo json_encode([]);
    }
} 
elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $venta = [
        'nombre_cliente' => $input['nombreCliente'],
        'kilos' => floatval($input['kilos']),
        'total' => floatval($input['total']),
        'estado' => $input['estado'],
        'fecha_registro' => date('Y-m-d H:i:s')
    ];
    $result = supabase("ventas", "POST", $venta);
    echo json_encode(['success' => !isset($result['error']), 'id' => $result[0]['id'] ?? null]);
} 
else {
    echo json_encode(['error' => 'Método no soportado']);
}
?>
