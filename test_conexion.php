<?php
// test_conexion.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de Conexión a Supabase</h1>";

$host = 'aws-0-us-east-2.pooler.supabase.com';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres.ownjmawswuygfhltlzts';
$password = 'Marin60563764';

echo "<p>Intentando conectar a: <code>$host:$port</code> con usuario <code>$user</code>...</p>";

if (!function_exists('pg_connect')) {
    die("<p style='color:red;'>❌ La extensión PostgreSQL (pgsql) NO está instalada en PHP.</p>");
} else {
    echo "<p style='color:green;'>✅ Extensión pgsql instalada.</p>";
}

$conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
$conn = @pg_connect($conn_string);

if (!$conn) {
    echo "<p style='color:red;'>❌ Error de conexión: " . pg_last_error() . "</p>";
    
    // Intento de diagnóstico adicional
    $ip = gethostbyname($host);
    echo "<p>Resolución DNS de $host: <b>$ip</b></p>";
    if ($ip === $host) {
        echo "<p style='color:orange;'>⚠️ No se pudo resolver el nombre del host. Revisa el host del pooler.</p>";
    }
} else {
    echo "<p style='color:green;'>✅ ¡Conexión exitosa a Supabase!</p>";
    
    // Verificar la tabla
    $result = pg_query($conn, "SELECT to_regclass('public.ventas')");
    $exists = pg_fetch_result($result, 0, 0);
    if ($exists) {
        echo "<p style='color:green;'>✅ La tabla 'ventas' existe.</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ La tabla 'ventas' NO existe. Debes crearla.</p>";
    }
}
?>
