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

$method = $_SERVER['REQUEST_METHOD'];

function callPython($action, $data = []) {
    $data['action'] = $action;
    $json_data = json_encode($data);
    $command = 'python3 ' . __DIR__ . '/../bridge.py';
    $descriptorspec = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
    $process = proc_open($command, $descriptorspec, $pipes);
    if (is_resource($process)) {
        fwrite($pipes[0], $json_data);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        proc_close($process);
        return json_decode($output, true);
    }
    return ['error' => 'No se pudo ejecutar Python'];
}

if ($method === 'GET') {
    if (isset($_GET['hoy'])) {
        $result = callPython('get_today', ['fecha' => date('Y-m-d')]);
        echo json_encode($result ?: []);
        exit;
    }
    if (isset($_GET['desde']) && isset($_GET['hasta'])) {
        $result = callPython('get_by_date_range', [
            'desde' => $_GET['desde'],
            'hasta' => $_GET['hasta']
        ]);
        echo json_encode($result ?: []);
        exit;
    }
    echo json_encode([]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $fechaHoraLocal = date('Y-m-d H:i:s');
    $venta = [
        'nombre_cliente' => $input['nombreCliente'],
        'kilos' => floatval($input['kilos']),
        'total' => floatval($input['total']),
        'estado' => $input['estado'],
        'fecha_registro' => $fechaHoraLocal
    ];
    $result = callPython('create', ['venta' => $venta]);
    if ($result && isset($result['success']) && $result['success'] === true) {
        echo json_encode(['success' => true, 'id' => $result['id_venta']]);
    } else {
        echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Error al guardar']);
    }
    exit;
}

if ($method === 'PUT') {
    $path = $_SERVER['PATH_INFO'] ?? '';
    if (preg_match('/^\/(\d+)\/pagar$/', $path, $matches)) {
        $result = callPython('pay', ['id' => intval($matches[1])]);
        echo json_encode($result ?: ['success' => false]);
        exit;
    }
}

echo json_encode(['error' => 'Método no permitido']);
?>
