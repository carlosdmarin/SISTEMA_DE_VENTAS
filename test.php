<?php
// test.php - Para encontrar el error real

// Mostrar TODOS los errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>TEST DE CONEXIÓN</h1>";

// 1. Verificar PostgreSQL
echo "<h2>1. Extensión PostgreSQL:</h2>";
if (function_exists('pg_connect')) {
    echo "✅ pg_connect EXISTE<br>";
} else {
    echo "❌ pg_connect NO EXISTE - Instala pgsql<br>";
}

// 2. Verificar archivo config.php
echo "<h2>2. Cargando config.php:</h2>";
try {
    require_once __DIR__ . '/api/config.php';
    echo "✅ config.php cargado<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 3. Probar conexión
echo "<h2>3. Probando conexión:</h2>";
try {
    $conn = getConnection();
    if ($conn) {
        echo "✅ CONEXIÓN EXITOSA a Supabase<br>";
        
        // 4. Verificar si existe tabla ventas
        $result = pg_query($conn, "SELECT to_regclass('public.ventas')");
        $exists = pg_fetch_result($result, 0, 0);
        
        if ($exists) {
            echo "✅ Tabla 'ventas' EXISTE<br>";
            
            // Contar registros
            $count = pg_query($conn, "SELECT COUNT(*) FROM ventas");
            $total = pg_fetch_result($count, 0, 0);
            echo "📊 Registros en ventas: $total<br>";
        } else {
            echo "❌ Tabla 'ventas' NO EXISTE<br>";
            echo "👉 Ejecuta: <a href='api/crear_tabla.php'>api/crear_tabla.php</a><br>";
        }
        
    } else {
        echo "❌ Error de conexión<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// 5. Probar ventas.php directamente
echo "<h2>5. Probando api/ventas.php?hoy=1:</h2>";
$url = "https://lavasoft-ho3h.onrender.com/api/ventas.php?hoy=1";
echo "<a href='$url' target='_blank'>Abrir ventas.php</a><br>";

// 6. Ver estructura de carpetas
echo "<h2>6. Estructura de archivos:</h2>";
echo "<pre>";
system("ls -la");
echo "</pre>";

echo "<h2>7. Logs de errores PHP:</h2>";
echo "<pre>";
$logFile = '/var/log/apache2/error.log';
if (file_exists($logFile)) {
    system("tail -50 $logFile");
} else {
    echo "No se encuentra el log de errores";
}
echo "</pre>";
?>
