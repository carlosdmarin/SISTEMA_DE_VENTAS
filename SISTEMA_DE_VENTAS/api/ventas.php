<?php
// Limpiar cualquier salida previa
ob_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Conexión a BD
$conn = new mysqli('localhost', 'root', '', 'lavasoft_db');

if ($conn->connect_error) {
    echo json_encode(['error' => 'Conexión fallida: ' . $conn->connect_error]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

// ========== GET: Ventas de hoy ==========
if ($method === 'GET' && isset($_GET['hoy'])) {
    // Usar LIKE para comparar solo la fecha (año-mes-día)
    $sql = "SELECT id_venta, nombre_cliente, kilos, total, estado, fecha_registro 
            FROM ventas 
            WHERE fecha_registro LIKE '2026-04-21%'
            ORDER BY fecha_registro DESC";
    
    $result = $conn->query($sql);
    
    if (!$result) {
        echo json_encode(['error' => 'Error en consulta: ' . $conn->error]);
        exit();
    }
    
    $ventas = [];
    while ($row = $result->fetch_assoc()) {
        $ventas[] = $row;
    }
    
    echo json_encode($ventas);
    exit();
}

// ========== GET: Historial por fecha ==========
if ($method === 'GET' && isset($_GET['desde']) && isset($_GET['hasta'])) {
    $desde = $conn->real_escape_string($_GET['desde']);
    $hasta = $conn->real_escape_string($_GET['hasta']);
    
    $sql = "SELECT id_venta, nombre_cliente, kilos, total, estado, fecha_registro 
            FROM ventas 
            WHERE DATE(fecha_registro) BETWEEN '$desde' AND '$hasta' 
            ORDER BY fecha_registro DESC";
    
    $result = $conn->query($sql);
    
    $ventas = [];
    while ($row = $result->fetch_assoc()) {
        $ventas[] = $row;
    }
    
    echo json_encode($ventas);
    exit();
}

// ========== POST: Guardar venta ==========
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $nombre = $conn->real_escape_string($data['nombreCliente']);
    $kilos = floatval($data['kilos']);
    $total = $kilos * 4;
    $estado = ($data['estado'] === 'pagado') ? 'cancelado' : $data['estado'];
    
    $sql = "INSERT INTO ventas (nombre_cliente, kilos, total, estado) 
            VALUES ('$nombre', $kilos, $total, '$estado')";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit();
}

// ========== PUT: Marcar como pagado ==========
if ($method === 'PUT') {
    $uri = $_SERVER['REQUEST_URI'];
    preg_match('/\/(\d+)\/pagar/', $uri, $matches);
    
    if (isset($matches[1])) {
        $id = intval($matches[1]);
        $sql = "UPDATE ventas SET estado = 'cancelado' WHERE id_venta = $id";
        
        if ($conn->query($sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ID no encontrado']);
    }
    exit();
}

echo json_encode(['error' => 'Ruta no encontrada']);
?>