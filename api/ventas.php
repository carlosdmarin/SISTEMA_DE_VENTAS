<?php
// api/ventas.php

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$conn = getConnection();

if (!$conn) {
    http_response_code(500);
    die(json_encode(['error' => 'Error de conexión a la base de datos']));
}

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';

// GET: Obtener ventas (hoy o por rango de fechas)
if ($method === 'GET') {
    if (isset($_GET['hoy']) && $_GET['hoy'] == '1') {
        $fecha = date('Y-m-d');
        $sql = "SELECT id_venta, nombre_cliente, kilos, total, estado, fecha_registro 
                FROM ventas 
                WHERE DATE(fecha_registro) = $1 
                ORDER BY fecha_registro DESC";
        $result = pg_query_params($conn, $sql, [$fecha]);
        
    } elseif (isset($_GET['desde']) && isset($_GET['hasta'])) {
        $desde = $_GET['desde'];
        $hasta = $_GET['hasta'];
        $sql = "SELECT id_venta, nombre_cliente, kilos, total, estado, fecha_registro 
                FROM ventas 
                WHERE DATE(fecha_registro) BETWEEN $1 AND $2 
                ORDER BY fecha_registro DESC";
        $result = pg_query_params($conn, $sql, [$desde, $hasta]);
        
    } else {
        $sql = "SELECT id_venta, nombre_cliente, kilos, total, estado, fecha_registro 
                FROM ventas 
                ORDER BY fecha_registro DESC LIMIT 100";
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

// POST: Crear nueva venta
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $nombreCliente = $input['nombreCliente'] ?? '';
    $kilos = floatval($input['kilos'] ?? 0);
    $total = floatval($input['total'] ?? 0);
    $estado = $input['estado'] ?? 'pendiente';
    
    if (empty($nombreCliente) || $kilos <= 0) {
        http_response_code(400);
        die(json_encode(['error' => 'Nombre y kilos son requeridos']));
    }
    
    $sql = "INSERT INTO ventas (nombre_cliente, kilos, total, estado) VALUES ($1, $2, $3, $4) RETURNING id_venta";
    $result = pg_query_params($conn, $sql, [$nombreCliente, $kilos, $total, $estado]);
    
    if (!$result) {
        http_response_code(500);
        die(json_encode(['error' => pg_last_error($conn)]));
    }
    
    $row = pg_fetch_assoc($result);
    echo json_encode(['success' => true, 'id_venta' => $row['id_venta']]);
    exit;
}

// PUT: Marcar venta como pagada
if ($method === 'PUT' && preg_match('/^\/(\d+)\/pagar$/', $path, $matches)) {
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

http_response_code(405);
echo json_encode(['error' => 'Método no permitido']);
