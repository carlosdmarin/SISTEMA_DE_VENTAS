<?php
// Configurar zona horaria de Perú
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

// Función mágica que llama a Python
function callPython($action, $data = []) {
    $data['action'] = $action;
    $json_data = json_encode($data);
    
    // Encontrar Python (puede ser python3 o python)
    $pythonPath = 'python3';
    if (PHP_OS_FAMILY === 'Windows') {
        $pythonPath = 'python';
    }
    
    $command = $pythonPath . ' ' . __DIR__ . '/bridge.py';
    $descriptorspec = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
    $process = proc_open($command, $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        fwrite($pipes[0], $json_data);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        
        if ($errors) {
            error_log("Python error: " . $errors);
        }
        
        $result = json_decode($output, true);
        if (!$result) {
            error_log("Python output no es JSON: " . $output);
            return ['error' => 'Error en respuesta de Python'];
        }
        return $result;
    }
    return ['error' => 'No se pudo ejecutar el script de Python'];
}

// --- Manejo de peticiones ---

// GET: Ventas de hoy o por rango de fechas
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

// POST: Guardar nueva venta
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
        exit;
    }
    
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
        $errorMsg = $result['error'] ?? 'Error desconocido';
        echo json_encode(['success' => false, 'error' => $errorMsg]);
    }
    exit;
}

// PUT: Marcar como pagado
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
