<?php
// api/config.php - Diagnóstico

$host = 'db.ownjmawswuygflhtlzts.supabase.co';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres';
$password = 'Marin60563764';

// Verificar si la función pg_connect existe
if (!function_exists('pg_connect')) {
    die(json_encode([
        'error' => 'PostgreSQL no está instalado en el servidor',
        'solution' => 'El Dockerfile necesita instalar pgsql'
    ]));
}

// Intentar conectar
$conn_str = "host=$host port=$port dbname=$dbname user=$user password=$password";
$conn = pg_connect($conn_str);

if (!$conn) {
    die(json_encode(['error' => 'Error de conexión: ' . pg_last_error()]));
}

echo json_encode(['success' => true, 'message' => 'Conectado a PostgreSQL']);
?>
