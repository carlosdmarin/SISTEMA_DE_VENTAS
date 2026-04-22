<?php
// api/ventas.php - Versión con API REST de Supabase
header('Content-Type: application/json');

// ========== CONFIGURACIÓN SUPABASE ==========
$supabase_url = 'https://ownjmawswuygfhltlzts.supabase.co';
$supabase_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im93bmptYXdzdXd5Z2ZsaHRsenRzIiwicm9sZSI6ImFub24iLCJpYXQiOjE3MTM5NjU4NjAsImV4cCI6MjAyOTU0MTg2MH0.VEkbC4CmJ8P2Cp6dxwVfILXZvRL9YJrmZ7VhqR2pGZg'; // <- REEMPLAZA ESTO CON TU API KEY ANON/PUBLIC
// ===========================================

$method = $_SERVER['REQUEST_METHOD'];

// ========== GET: Ventas de hoy ==========
if ($method === 'GET' && isset($_GET['hoy']) && $_GET['hoy'] == '1') {
    $hoy = date('Y-m-d');
    $url = "$supabase_url/rest/v1/ventas?select=*&fecha_registro=gte.$hoy&order=fecha_registro.desc";
    
    $options = [
        'http' => [
            'header' => [
                "apikey: $supabase_key",
                "Authorization: Bearer $supabase_key"
            ],
            'method' => 'GET'
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        // Si falla, devolver array vacío
        echo json_encode([]);
        exit;
    }
    
    $ventas = json_decode($response, true);
    echo json_encode($ventas ?: []);
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
    
    $data = [
        'nombre_cliente' => $nombre,
        'kilos' => $kilos,
        'total' => $total,
        'estado' => $estado
    ];
    
    $url = "$supabase_url/rest/v1/ventas";
    $options = [
        'http' => [
            'header' => [
                "apikey: $supabase_key",
                "Authorization: Bearer $supabase_key",
                "Content-Type: application/json",
                "Prefer: return=representation"
            ],
            'method' => 'POST',
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Error al guardar en Supabase']);
        exit;
    }
    
    $result = json_decode($response, true);
    echo json_encode(['success' => true, 'id_venta' => $result[0]['id_venta'] ?? 0]);
    exit;
}

// ========== PUT: Pagar venta ==========
if ($method === 'PUT') {
    $path = $_SERVER['PATH_INFO'] ?? '';
    
    if (preg_match('/^\/(\d+)\/pagar$/', $path, $matches)) {
        $id = intval($matches[1]);
        
        $url = "$supabase_url/rest/v1/ventas?id_venta=eq.$id";
        $data = ['estado' => 'cancelado'];
        
        $options = [
            'http' => [
                'header' => [
                    "apikey: $supabase_key",
                    "Authorization: Bearer $supabase_key",
                    "Content-Type: application/json",
                    "Prefer: return=minimal"
                ],
                'method' => 'PATCH',
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al actualizar']);
            exit;
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode([]);
?>
