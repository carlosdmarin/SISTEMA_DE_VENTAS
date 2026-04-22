<?php
// api/ventas.php - VERSIÓN NUCLEAR AUTO-CONTENIDA
header('Content-Type: application/json');

// ========== CONFIGURACIÓN DIRECTA ==========
$host = 'aws-0-us-east-2.pooler.supabase.com';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres.ownjmawswuygfhltlzts';
$password = 'Marin60563764';
// ==========================================

// Intentar conectar
$conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
$conn = @pg_connect($conn_string);

// Si falla la conexión, devolver error
if (!$conn) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'mensaje' => 'No se pudo conectar a Supabase',
        'detalle' => pg_last_error()
    ]);
    exit;
}

// Crear tabla si no existe
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

$method = $_SERVER['REQUEST_METHOD'];

// ========== GET: Ventas de hoy ==========
if ($method === 'GET' && isset($_GET['hoy'])) {
    $fecha = date('Y-m-d');
    $result = @pg_query_params($conn, 
        "SELECT id_venta, nombre_cliente, kilos, total, estado, fecha_registro 
         FROM ventas 
         WHERE DATE(fecha_registro) = $1 
         ORDER BY fecha_registro DESC",
        [$fecha]
    );
    
    if (!$result) {
        echo json_encode([]);
        exit;
    }
    
    $ventas = [];
    while ($row = pg_fetch_assoc($result)) {
        $ventas[] = $row;
    }
    
    echo json_encode($ventas);
    exit;
}

// ========== POST: Guardar venta ==========
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
    
    $result = @pg_query_params($conn,
        "INSERT INTO ventas (nombre_cliente, kilos, total, estado) VALUES ($1, $2, $3, $4) RETURNING id_venta",
        [$nombre, $kilos, $total, $estado]
    );
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => pg_last_error($conn)]);
        exit;
    }
    
    $row = pg_fetch_assoc($result);
    echo json_encode(['success' => true, 'id_venta' => $row['id_venta']]);
    exit;
}

// ========== PUT: Pagar venta ==========
if ($method === 'PUT') {
    $path = $_SERVER['PATH_INFO'] ?? '';
    
    if (preg_match('/^\/(\d+)\/pagar$/', $path, $matches)) {
        $id = intval($matches[1]);
        $result = @pg_query_params($conn,
            "UPDATE ventas SET estado = 'cancelado' WHERE id_venta = $1",
            [$id]
        );
        
        if (!$result) {
            http_response_code(500);
            echo json_encode(['error' => pg_last_error($conn)]);
            exit;
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
}

// Si llegamos aquí, método no soportado
echo json_encode([]);
?>
