<?php
// api/crear_tabla.php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

$conn = getConnection();

if (!$conn) {
    die(json_encode(['error' => 'Sin conexión a BD']));
}

// Crear tabla ventas
$sql = "
CREATE TABLE IF NOT EXISTS ventas (
    id_venta SERIAL PRIMARY KEY,
    nombre_cliente VARCHAR(255) NOT NULL,
    kilos DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    estado VARCHAR(20) DEFAULT 'pendiente',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$result = pg_query($conn, $sql);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Tabla ventas creada correctamente']);
} else {
    echo json_encode(['error' => pg_last_error($conn)]);
}
?>
