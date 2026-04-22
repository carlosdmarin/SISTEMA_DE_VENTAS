<?php
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// Función mágica que llama a Python
function callPython($action, $data = []) {
    $data['action'] = $action;
    $json_data = json_encode($data);
    $command = 'python3 ' . __DIR__ . '/bridge.py';
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
    return ['error' => 'No se pudo ejecutar el script de Python'];
}

// --- Manejo de peticiones ---
if ($method === 'GET' && isset($_GET['hoy'])) {
    $result = callPython('get_today', ['fecha' => date('Y-m-d')]);
    echo json_encode($result ?: []);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $venta = [
        'nombre_cliente' => $input['nombreCliente'],
        'kilos' => floatval($input['kilos']),
        'total' => floatval($input['total']),
        'estado' => $input['estado']
    ];
    $result = callPython('create', ['venta' => $venta]);
    echo json_encode($result ? ['success' => true, 'id_venta' => $result['id_venta']] : ['error' => 'Error al guardar']);
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
