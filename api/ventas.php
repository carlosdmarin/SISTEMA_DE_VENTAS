<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Conexión a PostgreSQL (Supabase)
require_once 'config.php';

// La conexión ya está en $conn (de config.php)
$method = $_SERVER['REQUEST_METHOD'];

// ========== GET: Ventas de hoy ==========
if ($method === 'GET' && isset($_GET['hoy'])) {
    $fecha = date('Y-m-d');
    
    $sql = "SELECT id_venta, nombre_cliente, kilos, total, estado, fecha_registro 
            FROM ventas 
            WHERE DATE(fecha_registro) = '$fecha'
            ORDER BY fecha_registro DESC";
    
    $result = pg_query($conn, $sql);
    
    if (!$result) {
        echo json_encode(['error' => 'Error en consulta: ' . pg_last_error($conn)]);
        exit();
    }
    
    $ventas = [];
    while ($row = pg_fetch_assoc($result)) {
        $ventas[] = $row;
    }
    
    echo json_encode($ventas);
    exit();
}

// ========== GET: Historial por fecha ==========
if ($method === 'GET' && isset($_GET['desde']) && isset($_GET['hasta'])) {
    $desde = pg_escape_string($conn, $_GET['desde']);
    $hasta = pg_escape_string($conn, $_GET['hasta']);
    
    $sql = "SELECT id_venta, nombre_cliente, kilos, total, estado, fecha_registro 
            FROM ventas 
            WHERE DATE(fecha_registro) BETWEEN '$desde' AND '$hasta' 
            ORDER BY fecha_registro DESC";
    
    $result = pg_query($conn, $sql);
    
    $ventas = [];
    while ($row = pg_fetch_assoc($result)) {
        $ventas[] = $row;
    }
    
    echo json_encode($ventas);
    exit();
}

// ========== POST: Guardar venta ==========
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $nombre = pg_escape_string($conn, $data['nombreCliente']);
    $kilos = floatval($data['kilos']);
    $total = $kilos * 4;
    $estado = ($data['estado'] === 'pagado') ? 'cancelado' : $data['estado'];
    
    $sql = "INSERT INTO ventas (nombre_cliente, kilos, total, estado) 
            VALUES ('$nombre', $kilos, $total, '$estado') RETURNING id_venta";
    
    $result = pg_query($conn, $sql);
    
    if ($result) {
        $row = pg_fetch_assoc($result);
        echo json_encode(['success' => true, 'id' => $row['id_venta']]);
    } else {
        echo json_encode(['success' => false, 'error' => pg_last_error($conn)]);
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
        
        if (pg_query($conn, $sql)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => pg_last_error($conn)]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ID no encontrado']);
    }
    exit();
}

echo json_encode(['error' => 'Ruta no encontrada']);
?>
