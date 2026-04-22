<?php
// api/config.php

header('Content-Type: application/json');

// ========== CONFIGURACIÓN DEL POOLER ==========
$host = 'aws-0-us-east-2.pooler.supabase.com';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres.ownjmawswuygfhltlzts';
$password = 'Marin60563764';
// ===========================================

function getConnection() {
    global $host, $port, $dbname, $user, $password;
    
    if (!function_exists('pg_connect')) {
        error_log("ERROR: La extensión pgsql no está instalada.");
        return null;
    }
    
    $conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
    error_log("Intentando conectar con: " . $conn_string);
    
    $conn = @pg_connect($conn_string);
    
    if (!$conn) {
        error_log("ERROR DE CONEXIÓN: " . pg_last_error());
        return null;
    }
    
    return $conn;
}
?>
