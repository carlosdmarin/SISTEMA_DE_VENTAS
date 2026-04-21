<?php
// ============================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================

// Para desarrollo local (XAMPP/WAMP)
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'lavasoft_db';

// Para producción (cuando subas a hosting)
// $host = 'tu_hosting_mysql';
// $user = 'tu_usuario';
// $password = 'tu_contraseña';
// $database = 'tu_base_datos';

// Crear conexión
$conn = new mysqli($host, $user, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die(json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]));
}

// Configurar charset para UTF-8
$conn->set_charset("utf8mb4");

// No cerrar la conexión aquí, se cierra al final del script
?>