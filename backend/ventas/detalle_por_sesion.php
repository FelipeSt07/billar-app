<?php
require "../config/database.php";

$id_sesion = $_GET['id_sesion'] ?? null;

if (!$id_sesion) {
    echo json_encode(["success" => false]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        d.id_detalle,
        p.nombre,
        d.cantidad,
        d.precio_unitario,
        d.subtotal
    FROM detalle_venta d
    JOIN productos p ON d.id_producto = p.id_producto
    WHERE d.id_venta = (
        SELECT id_venta FROM ventas WHERE id_sesion = ? LIMIT 1
    )
");

$stmt->execute([$id_sesion]);

echo json_encode([
    "success" => true,
    "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
