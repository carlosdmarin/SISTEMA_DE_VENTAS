<?php
// api/config.php

// Datos del Session Pooler (Agrupador de sesiones)
$host = 'aws-0-sa-east-1.pooler.supabase.com';  // Si no funciona, cambia 'sa-east-1' por 'us-east-1'
$port = '5432';
$dbname = 'postgres';
$user = 'postgres.ownjmawswuygflhtlzts';  // Usuario con el ID del proyecto
$password = 'Marin60563764';

if (!function_exists('pg_connect')) {
    die(json_encode(['error' => 'PostgreSQL no está instalado']));
}

$conn_str = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
$conn = @pg_connect($conn_str);

if (!$conn) {
    die(json_encode([
        'error' => 'Error de conexión',
        'mensaje' => pg_last_error(),
        'sugerencia' => 'Verifica el host exacto en Dashboard → Botón verde "Connect" → agrupador de sesiones'
    ]));
}

echo json_encode(['success' => true, 'message' => '¡Conectado a Supabase!']);
?>
