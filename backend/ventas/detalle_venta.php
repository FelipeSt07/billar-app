<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$idVenta = $_GET["id_venta"] ?? null;

if (!$idVenta) {
  jsonResponse(["success" => false, "message" => "ID de venta requerido"]);
}

$stmt = $pdo->prepare("
  SELECT
    v.id_venta,
    v.fecha_venta,
    v.total,
    v.tipo_venta,
    v.estado,
    p.nombre,
    d.cantidad,
    d.precio_unitario,
    d.subtotal
  FROM ventas v
  JOIN detalle_venta d ON d.id_venta = v.id_venta
  JOIN productos p ON p.id_producto = d.id_producto
  WHERE v.id_venta = ?
");
$stmt->execute([$idVenta]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
  jsonResponse(["success" => false, "message" => "Venta no encontrada"]);
}

jsonResponse([
  "success" => true,
  "data" => $rows
]);
