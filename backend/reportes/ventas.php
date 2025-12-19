<?php
require "../config/database.php";

$stmt = $pdo->query("
    SELECT 
        v.id_venta,
        v.fecha_venta,
        v.total,
        v.metodo_pago,
        u.nombre AS usuario,
        s.id_sesion,
        m.nombre_mesa
    FROM ventas v
    INNER JOIN usuarios u ON v.id_usuario = u.id_usuario
    INNER JOIN sesiones_mesa s ON v.id_sesion = s.id_sesion
    INNER JOIN mesas m ON s.id_mesa = m.id_mesa
    ORDER BY v.fecha_venta DESC
");

$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "data" => $ventas
]);
