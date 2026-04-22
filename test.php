<?php
// test.php - Diagnóstico de conexión
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico de Conexión a Supabase</h1>";

// 1. Verificar extensión pgsql
echo "<h2>1. Extensión PostgreSQL:</h2>";
if (function_exists('pg_connect')) {
    echo "<p style='color:green;'>✅ pg_connect EXISTE</p>";
} else {
    echo "<p style='color:red;'>❌ pg_connect NO EXISTE - ¡Instala pgsql!</p>";
    die();
}

// 2. Datos de conexión
$host = 'aws-0-us-east-2.pooler.supabase.com';
$port = '5432';
$dbname = 'postgres';
$user = 'postgres.ownjmawswuygfhltlzts';
$password = 'Marin60563764';

echo "<h2>2. Intentando conexión:</h2>";
echo "<p>Host: <code>$host</code></p>";
echo "<p>Puerto: <code>$port</code></p>";
echo "<p>Usuario: <code>$user</code></p>";

// 3. Resolver DNS
echo "<h2>3. Resolución DNS:</h2>";
$ip = gethostbyname($host);
if ($ip === $host) {
    echo "<p style='color:red;'>❌ No se pudo resolver el host: $host</p>";
    echo "<p>Posible causa: El servidor no tiene soporte DNS o el host es inválido.</p>";
} else {
    echo "<p style='color:green;'>✅ Host resuelto a: $ip</p>";
}

// 4. Intentar conexión
echo "<h2>4. Probando conexión PostgreSQL:</h2>";
$conn_string = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=require";
echo "<p>Connection string: <code>$conn_string</code></p>";

$conn = @pg_connect($conn_string);

if (!$conn) {
    $error = error_get_last();
    echo "<p style='color:red;'>❌ ERROR DE CONEXIÓN:</p>";
    echo "<pre style='background:#fee;padding:10px;'>" . pg_last_error() . "</pre>";
    
    // Intentar con SSL desactivado (para diagnóstico)
    echo "<p>Probando sin SSL (solo diagnóstico):</p>";
    $conn_string_no_ssl = "host=$host port=$port dbname=$dbname user=$user password=$password";
    $conn2 = @pg_connect($conn_string_no_ssl);
    if ($conn2) {
        echo "<p style='color:orange;'>✅ Conexión SIN SSL funciona. El problema es con SSL.</p>";
    } else {
        echo "<p style='color:red;'>❌ Conexión SIN SSL también falla.</p>";
    }
} else {
    echo "<p style='color:green;font-size:24px;'>✅ ¡CONEXIÓN EXITOSA A SUPABASE!</p>";
    
    // Verificar tabla
    $result = pg_query($conn, "SELECT to_regclass('public.ventas')");
    $exists = pg_fetch_result($result, 0, 0);
    
    if ($exists) {
        echo "<p style='color:green;'>✅ Tabla 'ventas' EXISTE</p>";
        
        // Contar registros
        $count = pg_query($conn, "SELECT COUNT(*) FROM ventas");
        $total = pg_fetch_result($count, 0, 0);
        echo "<p>📊 Registros en ventas: <b>$total</b></p>";
    } else {
        echo "<p style='color:orange;'>⚠️ Tabla 'ventas' NO EXISTE</p>";
        
        // Crear tabla
        echo "<p>Intentando crear tabla...</p>";
        $create = pg_query($conn, "
            CREATE TABLE IF NOT EXISTS ventas (
                id_venta SERIAL PRIMARY KEY,
                nombre_cliente VARCHAR(255) NOT NULL,
                kilos DECIMAL(10,2) NOT NULL,
                total DECIMAL(10,2) NOT NULL,
                estado VARCHAR(20) DEFAULT 'pendiente',
                fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        if ($create) {
            echo "<p style='color:green;'>✅ Tabla 'ventas' CREADA</p>";
        } else {
            echo "<p style='color:red;'>❌ Error al crear tabla: " . pg_last_error($conn) . "</p>";
        }
    }
    
    pg_close($conn);
}

// 5. Verificar conectividad de red
echo "<h2>5. Conectividad de red:</h2>";
$socket = @fsockopen($host, $port, $errno, $errstr, 5);
if ($socket) {
    echo "<p style='color:green;'>✅ Puerto $port accesible en $host</p>";
    fclose($socket);
} else {
    echo "<p style='color:red;'>❌ No se puede conectar al puerto $port: $errstr ($errno)</p>";
}

// 6. Información del servidor
echo "<h2>6. Información del servidor PHP:</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Sistema: " . php_uname() . "</p>";
echo "<p>OpenSSL: " . (extension_loaded('openssl') ? '✅' : '❌') . "</p>";
echo "<p>PDO PostgreSQL: " . (extension_loaded('pdo_pgsql') ? '✅' : '❌') . "</p>";
?>
