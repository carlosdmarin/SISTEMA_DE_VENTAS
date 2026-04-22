<?php
// api/config.php

function getConnection() {
    // Usar IP directa en lugar de nombre de host (evita problemas DNS)
    $host = '3.131.105.52';  // IP de aws-0-us-east-2.pooler.supabase.com
    $port = '5432';
    $dbname = 'postgres';
    $user = 'postgres.ownjmawswuygfhltlzts';
    $password = 'Marin60563764';
    
    if (!function_exists('pg_connect')) {
        return null;
    }
    
    // Forzar IPv4
    $conn_str = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
    $conn = @pg_connect($conn_str);
    
    return $conn;
}
?>
