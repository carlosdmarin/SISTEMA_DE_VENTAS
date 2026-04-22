<?php
// api/config.php

// Forzar que la respuesta sea JSON
header('Content-Type: application/json');

// ========== DATOS CORRECTOS PARA OHIO (us-east-2) ==========
$host = 'aws-0-us-east-2.pooler.supabase.com';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres.ownjmawswuygfhltlzts';
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
        'detalle' => pg_last_error()
    ]));
}

echo json_encode(['success' => true, 'message' => '¡Conectado a Supabase correctamente!']);
