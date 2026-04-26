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

// ==========================================
// CONEXIÓN A SUPABASE CON SSL OBLIGATORIO
// ==========================================

$db_host = getenv('SUPABASE_HOST') ?: 'aws-1-us-east-2.pooler.supabase.com';
$db_port = getenv('SUPABASE_PORT') ?: '6543';
$db_name = getenv('SUPABASE_DB') ?: 'lavasoft_db';
$db_user = getenv('SUPABASE_USER') ?: 'postgres';
$db_pass = getenv('SUPABASE_PASSWORD') ?: '';

// Verificar que tenemos todos los datos
if (empty($db_host) || empty($db_user) || empty($db_pass)) {
    echo json_encode(['error' => 'Faltan variables de entorno de Supabase']);
    exit();
}

// Intentar diferentes opciones de conexión
$connection_string = "pgsql:host=$db_host;port=$db_port;dbname=$db_name;sslmode=require";

try {
    $pdo = new PDO($connection_string, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_PERSISTENT => false
    ]);
} catch (PDOException $e) {
    // Si falla con sslmode=require, intentar sin SSL
    try {
        $connection_string = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
        $pdo = new PDO($connection_string, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 30
        ]);
    } catch (PDOException $e2) {
        echo json_encode(['error' => 'Conexión fallida: ' . $e2->getMessage()]);
        exit();
    }
}

$method = $_SERVER['REQUEST_METHOD'];

// ==========================================
// GET: Ventas de hoy
// ==========================================
if ($method === 'GET' && isset($_GET['hoy'])) {
    $hoy = date('Y-m-d');
    $sql = "SELECT id_venta, nombre_cliente, kilos, total, estado, fecha_registro 
            FROM ventas 
            WHERE DATE(fecha_registro) = :hoy 
            ORDER BY fecha_registro DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':hoy' => $hoy]);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($ventas);
    exit;
}

// ==========================================
// GET: Historial por fechas
// ==========================================
if ($method === 'GET' && isset($_GET['desde']) && isset($_GET['hasta'])) {
    $desde = $_GET['desde'];
    $hasta = $_GET['hasta'];
    $sql = "SELECT id_venta, nombre_cliente, kilos, total, estado, fecha_registro 
            FROM ventas 
            WHERE DATE(fecha_registro) BETWEEN :desde AND :hasta 
            ORDER BY fecha_registro DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($ventas);
    exit;
}

// ==========================================
// POST: Guardar nueva venta
// ==========================================
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
        exit;
    }
    
    $nombre = $input['nombreCliente'];
    $kilos = floatval($input['kilos']);
    $total = $kilos * 4;
    $estado = ($input['estado'] === 'pagado') ? 'cancelado' : $input['estado'];
    $fechaHoraLocal = date('Y-m-d H:i:s');
    
    $sql = "INSERT INTO ventas (nombre_cliente, kilos, total, estado, fecha_registro) 
            VALUES (:nombre, :kilos, :total, :estado, :fecha_registro) 
            RETURNING id_venta";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $nombre,
        ':kilos' => $kilos,
        ':total' => $total,
        ':estado' => $estado,
        ':fecha_registro' => $fechaHoraLocal
    ]);
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'id' => $row['id_venta']]);
    exit;
}

// ==========================================
// PUT: Marcar como pagado
// ==========================================
if ($method === 'PUT') {
    $path = $_SERVER['PATH_INFO'] ?? '';
    if (preg_match('/^\/(\d+)\/pagar$/', $path, $matches)) {
        $id = intval($matches[1]);
        $sql = "UPDATE ventas SET estado = 'cancelado' WHERE id_venta = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['error' => 'Método no permitido']);
?>
