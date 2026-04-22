<?php
// api/ventas.php - VERSIÓN BLINDADA SIN ERRORES

// Activar reporte de errores para debug (quitar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en pantalla
ini_set('log_errors', 1);

// Forzar JSON SIEMPRE
header('Content-Type: application/json');

// Capturar cualquier error fatal y convertirlo a JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode(['error' => 'Error interno del servidor', 'detalle' => $error['message']]);
        exit;
    }
});

// Capturar excepciones no manejadas
set_exception_handler(function($e) {
    http_response_code(500);
    echo json_encode(['error' => 'Excepción no manejada', 'detalle' => $e->getMessage()]);
    exit;
});

try {
    // Incluir configuración
    require_once __DIR__ . '/config.php';
    
    $conn = getConnection();
    
    if (!$conn) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de conexión a la base de datos']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['PATH_INFO'] ?? '';
    
    // ========== GET: Obtener ventas ==========
    if ($method === 'GET') {
        $ventas = [];
        
        // Ventas de hoy
        if (isset($_GET['hoy']) && $_GET['hoy'] == '1') {
            $fecha = date('Y-m-d');
            $sql = "SELECT id_venta, nombre_cliente, kilos, total, estado, fecha_registro 
                    FROM ventas 
                    WHERE DATE(fecha_registro) = $1 
                    ORDER BY fecha_registro DESC";
            $result = pg_query_params($conn, $sql, [$fecha]);
            
            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }
            
            while ($row = pg_fetch_assoc($result)) {
                $ventas[] = $row;
            }
        }
        // Ventas por rango de fechas
        elseif (isset($_GET['desde']) && isset($_GET['hasta'])) {
            $desde = $_GET['desde'];
            $hasta = $_GET['hasta'];
            $sql = "SELECT id_venta, nombre_cliente, kilos, total, estado, fecha_registro 
                    FROM ventas 
                    WHERE DATE(fecha_registro) BETWEEN $1 AND $2 
                    ORDER BY fecha_registro DESC";
            $result = pg_query_params($conn, $sql, [$desde, $hasta]);
            
            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }
            
            while ($row = pg_fetch_assoc($result)) {
                $ventas[] = $row;
            }
        }
        // Todas las ventas (límite 100)
        else {
            $sql = "SELECT id_venta, nombre_cliente, kilos, total, estado, fecha_registro 
                    FROM ventas 
                    ORDER BY fecha_registro DESC LIMIT 100";
            $result = pg_query($conn, $sql);
            
            if (!$result) {
                throw new Exception(pg_last_error($conn));
            }
            
            while ($row = pg_fetch_assoc($result)) {
                $ventas[] = $row;
            }
        }
        
        echo json_encode($ventas);
        exit;
    }
    
    // ========== POST: Crear venta ==========
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Datos inválidos']);
            exit;
        }
        
        $nombreCliente = trim($input['nombreCliente'] ?? '');
        $kilos = floatval($input['kilos'] ?? 0);
        $total = floatval($input['total'] ?? 0);
        $estado = $input['estado'] ?? 'pendiente';
        
        if (empty($nombreCliente) || $kilos <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Nombre y kilos son requeridos']);
            exit;
        }
        
        $sql = "INSERT INTO ventas (nombre_cliente, kilos, total, estado) VALUES ($1, $2, $3, $4) RETURNING id_venta";
        $result = pg_query_params($conn, $sql, [$nombreCliente, $kilos, $total, $estado]);
        
        if (!$result) {
            throw new Exception(pg_last_error($conn));
        }
        
        $row = pg_fetch_assoc($result);
        echo json_encode(['success' => true, 'id_venta' => $row['id_venta']]);
        exit;
    }
    
    // ========== PUT: Pagar venta ==========
    if ($method === 'PUT' && preg_match('/^\/(\d+)\/pagar$/', $path, $matches)) {
        $id = intval($matches[1]);
        
        $sql = "UPDATE ventas SET estado = 'cancelado' WHERE id_venta = $1";
        $result = pg_query_params($conn, $sql, [$id]);
        
        if (!$result) {
            throw new Exception(pg_last_error($conn));
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Método no permitido
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error del servidor',
        'detalle' => $e->getMessage()
    ]);
}
