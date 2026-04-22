<?php
// api/config.php - Conexión a Supabase (PostgreSQL)

$host = 'db.ownjmawswuygflhtlzts.supabase.co';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres';
$password = 'Marin60563764';

// Cadena de conexión para PostgreSQL
$conn_str = "host=$host port=$port dbname=$dbname user=$user password=$password";

// Intentar conectar
$conn = pg_connect($conn_str);

if (!$conn) {
    die(json_encode(['error' => 'Error de conexión: ' . pg_last_error()]));
}

// Establecer zona horaria
pg_query($conn, "SET TIME ZONE 'America/Lima'");
?>
