<?php
// api/ventas.php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$conn = getConnection();

if (!$conn) {
    http_response_code(500);
    die(json_encode(['error' => 'Error de conexión']));
}

$method = $_SERVER['REQUEST_METHOD'];

// GET: Obtener ventas
if ($method === 'GET') {
    if (isset($_GET['hoy']) && $_GET['hoy'] == '1') {
        $fecha = date('Y-m-d');
        $sql = "SELECT * FROM ventas WHERE DATE(fecha_registro) = $1 ORDER BY fecha_registro DESC";
        $result = pg_query_params($conn, $sql, [$fecha]);
    } 
    else if (isset($_GET['desde']) && isset($_GET['hasta'])) {
        $sql = "SELECT * FROM ventas WHERE DATE(fecha_registro) BETWEEN $1 AND $2 ORDER BY fecha_registro DESC";
        $result = pg_query_params($conn, $sql, [$_GET['desde'], $_GET['hasta']]);
    }
    else {
        $sql = "SELECT * FROM ventas ORDER BY fecha_registro DESC LIMIT 100";
        $result = pg_query($conn, $sql);
    }
    
    if (!$result) {
        http_response_code(500);
        die(json_encode(['error' => pg_last_error($conn)]));
    }
    
    $ventas = [];
    while ($row = pg_fetch_assoc($result)) {
        $ventas[] = $row;
    }
    
    echo json_encode($ventas);
    exit;
}

// POST: Crear venta
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $nombre = $input['nombreCliente'] ?? '';
    $kilos = floatval($input['kilos'] ?? 0);
    $total = floatval($input['total'] ?? 0);
    $estado = $input['estado'] ?? 'pendiente';
    
    if (empty($nombre) || $kilos <= 0) {
        http_response_code(400);
        die(json_encode(['error' => 'Datos inválidos']));
    }
    
    $sql = "INSERT INTO ventas (nombre_cliente, kilos, total, estado) VALUES ($1, $2, $3, $4) RETURNING id_venta";
    $result = pg_query_params($conn, $sql, [$nombre, $kilos, $total, $estado]);
    
    if (!$result) {
        http_response_code(500);
        die(json_encode(['error' => pg_last_error($conn)]));
    }
    
    $row = pg_fetch_assoc($result);
    echo json_encode(['success' => true, 'id_venta' => $row['id_venta']]);
    exit;
}

// PUT: Pagar venta
if ($method === 'PUT') {
    $path = $_SERVER['PATH_INFO'] ?? '';
    if (preg_match('/^\/(\d+)\/pagar$/', $path, $matches)) {
        $id = intval($matches[1]);
        $sql = "UPDATE ventas SET estado = 'cancelado' WHERE id_venta = $1";
        $result = pg_query_params($conn, $sql, [$id]);
        
        if (!$result) {
            http_response_code(500);
            die(json_encode(['error' => pg_last_error($conn)]));
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
?>
