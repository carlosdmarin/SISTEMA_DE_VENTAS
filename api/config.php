<?php
// api/config.php

// Opción 1: Intentar con el pooler deducido (más probable)
$host = 'aws-0-us-east-1.pooler.supabase.com';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres.ownjmawswuygflhtlzts';  // Nota: usuario con ID del proyecto
$password = 'Marin60563764';

if (!function_exists('pg_connect')) {
    die(json_encode(['error' => 'PostgreSQL no está instalado']));
}

$conn_str = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
$conn = @pg_connect($conn_str);

if ($conn) {
    echo json_encode(['success' => true, 'message' => 'Conectado con Session Pooler']);
    exit;
}

// Opción 2: Si falla, probar con Transaction Pooler (puerto diferente)
$port = '6543';
$conn_str = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
$conn = @pg_connect($conn_str);

if ($conn) {
    echo json_encode(['success' => true, 'message' => 'Conectado con Transaction Pooler']);
    exit;
}

// Opción 3: Probar con usuario simple "postgres" (sin ID)
$user = 'postgres';
$port = '5432';
$conn_str = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
$conn = @pg_connect($conn_str);

if ($conn) {
    echo json_encode(['success' => true, 'message' => 'Conectado con usuario postgres']);
    exit;
}

// Opción 4: Forzar IPv4 en conexión directa (último recurso)
putenv('RES_OPTIONS=inet6:off');
$host = 'db.ownjmawswuygflhtlzts.supabase.co';
$port = '5432';
$user = 'postgres';
$conn_str = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
$conn = @pg_connect($conn_str);

if (!$conn) {
    die(json_encode([
        'error' => 'Todas las conexiones fallaron',
        'solucion' => 'Ve a Supabase Dashboard → Project Settings → Database → Busca los datos de Connection Pooling'
    ]));
}

echo json_encode(['success' => true, 'message' => 'Conectado']);
?>
