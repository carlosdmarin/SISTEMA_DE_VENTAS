<?php
date_default_timezone_set('America/Lima');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$supabase_url = getenv('SUPABASE_URL');
$supabase_key = getenv('SUPABASE_KEY');

if (!$supabase_url || !$supabase_key) {
    echo json_encode(["error" => "Faltan SUPABASE_URL o SUPABASE_KEY"]);
    exit;
}

function supabase_request($endpoint, $method = 'GET', $data = null) {
    global $supabase_url, $supabase_key;
    $ch = curl_init("$supabase_url/rest/v1/$endpoint");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $supabase_key",
        "Authorization: Bearer $supabase_key",
        "Content-Type: application/json"
    ]);
    if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && isset($_GET['hoy'])) {
    $hoy = date('Y-m-d');
    $data = supabase_request("ventas?fecha_registro=gte=$hoy&fecha_registro=lt=" . date('Y-m-d', strtotime('+1 day')));
    echo json_encode($data ?: []);
}
elseif ($method === 'GET' && isset($_GET['desde'], $_GET['hasta'])) {
    $data = supabase_request("ventas?fecha_registro=gte=" . $_GET['desde'] . "&fecha_registro=lte=" . $_GET['hasta']);
    echo json_encode($data ?: []);
}
elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $data = supabase_request("ventas", "POST", [
        'nombre_cliente' => $input['nombreCliente'],
        'kilos' => (float)$input['kilos'],
        'total' => (float)$input['total'],
        'estado' => $input['estado'],
        'fecha_registro' => date('Y-m-d H:i:s')
    ]);
    echo json_encode(['success' => !isset($data['error']), 'id' => $data[0]['id'] ?? null]);
}
else echo json_encode([]);
?>
