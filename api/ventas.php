<?php
// api/ventas.php
header('Content-Type: application/json');

// Incluir configuración
require_once __DIR__ . '/config.php';

$conn = getConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión: ' . pg_last_error()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// GET: Ventas de hoy
if ($method === 'GET' && isset($_GET['hoy']) && $_GET['hoy'] == '1') {
    $fecha = date('Y-m-d');
    
    // Primero intentar crear la tabla si no existe
    @pg_query($conn, "
        CREATE TABLE IF NOT EXISTS ventas (
            id_venta SERIAL PRIMARY KEY,
            nombre_cliente VARCHAR(255) NOT NULL,
            kilos DECIMAL(10,2) NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            estado VARCHAR(20) DEFAULT 'pendiente',
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    $sql = "SELECT * FROM ventas WHERE DATE(fecha_registro) = $1 ORDER BY fecha_registro DESC";
    $result = pg_query_params($conn, $sql, [$fecha]);
    
    if (!$result) {
        echo json_encode([]);  // Devolver array vacío si hay error
        exit;
    }
    
    $ventas = [];
    while ($row = pg_fetch_assoc($result)) {
        $ventas[] = $row;
    }
    
    echo json_encode($ventas);
    exit;
}

// POST: Guardar venta
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $nombre = trim($input['nombreCliente'] ?? '');
    $kilos = floatval($input['kilos'] ?? 0);
    $total = floatval($input['total'] ?? 0);
    $estado = $input['estado'] ?? 'pendiente';
    
    if (empty($nombre) || $kilos <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Nombre y kilos son requeridos']);
        exit;
    }
    
    $sql = "INSERT INTO ventas (nombre_cliente, kilos, total, estado) VALUES ($1, $2, $3, $4) RETURNING id_venta";
    $result = pg_query_params($conn, $sql, [$nombre, $kilos, $total, $estado]);
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => pg_last_error($conn)]);
        exit;
    }
    
    $row = pg_fetch_assoc($result);
    echo json_encode(['success' => true, 'id_venta' => $row['id_venta']]);
    exit;
}

echo json_encode([]);
?>
