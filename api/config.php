<?php
// api/config.php

// Forzar respuesta JSON
header('Content-Type: application/json');

// Datos del Session Pooler (Ohio)
$host = 'aws-0-us-east-2.pooler.supabase.com';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres.ownjmawswuygfhltlzts';
$password = 'Marin60563764';

function getConnection() {
    global $host, $port, $dbname, $user, $password;
    
    if (!function_exists('pg_connect')) {
        return null;
    }
    
    $conn_str = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
    return @pg_connect($conn_str);
}
