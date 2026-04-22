<?php
// api/config.php

// ========== DATOS CORRECTOS PARA OHIO (us-east-2) ==========
$host = 'aws-0-us-east-2.pooler.supabase.com';  // Pooler compatible con IPv4
$port = '5432';  // Session pooler usa 5432 (NO 6543)
$dbname = 'postgres';
$user = 'postgres.ownjmawswuygfhltlzts';  // Usuario con el ID del proyecto
$password = 'Marin60563764';
// ===========================================================

if (!function_exists('pg_connect')) {
    die(json_encode(['error' => 'PostgreSQL no está instalado']));
}

$conn_str = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
$conn = @pg_connect($conn_str);

if (!$conn) {
    die(json_encode([
        'error' => 'Error de conexión',
        'detalle' => pg_last_error(),
        'solucion' => 'Verifica que en el Dashboard seleccionaste "agrupador de sesiones"'
    ]));
}

echo json_encode(['success' => true, 'message' => '¡Conectado a Supabase correctamente!']);
?>
