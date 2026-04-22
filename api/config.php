<?php
// api/config.php
// NO poner header() aquí - Este archivo es solo para configuración

function getConnection() {
    $host = 'aws-0-us-east-2.pooler.supabase.com';
    $port = '5432';
    $dbname = 'postgres';
    $user = 'postgres.ownjmawswuygfhltlzts';
    $password = 'Marin60563764';
    
    if (!function_exists('pg_connect')) {
        return null;
    }
    
    $conn_str = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
    $conn = @pg_connect($conn_str);
    
    return $conn;
}
?>
