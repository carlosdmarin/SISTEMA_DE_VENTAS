<?php
// health.php - Mantiene tu app despierta en Render
header('Content-Type: application/json');

// Respuesta básica sin conectar a base de datos (para evitar errores)
$response = [
    "status" => "ok",
    "service" => "lavanderia_api",
    "timestamp" => date('Y-m-d H:i:s')
];

// Si quieres verificar que la base de datos responde (OPCIONAL, pero recomendado)
// Descomenta las líneas de abajo si tienes un archivo de conexión a Supabase
/*
try {
    // Ajusta la ruta según tu proyecto
    require_once 'conexion_supabase.php';
    // Ejecuta una consulta ligera
    $stmt = $pdo->query("SELECT 1");
    $response["db_connected"] = true;
} catch (Exception $e) {
    $response["db_connected"] = false;
    $response["db_error"] = $e->getMessage();
}
*/

echo json_encode($response);
?>
