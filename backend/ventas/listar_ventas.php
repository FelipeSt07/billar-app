<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$inicio = $_GET["inicio"] ?? null;
$fin = $_GET["fin"] ?? null;

$sql = "
  SELECT
    v.id_venta,
    v.fecha_venta,
    v.total,
    v.estado,
    u.nombre AS usuario,
    COALESCE(SUM(d.subtotal_costo), 0) AS costo_total,
    (v.total - COALESCE(SUM(d.subtotal_costo), 0)) AS utilidad
  FROM ventas v
  JOIN usuarios u ON u.id_usuario = v.id_usuario
  LEFT JOIN detalle_venta d ON d.id_venta = v.id_venta
  WHERE 1
";

$params = [];

if ($inicio && $fin) {
  $sql .= " AND DATE(v.fecha_venta) BETWEEN ? AND ?";
  $params[] = $inicio;
  $params[] = $fin;
}

$sql .= " GROUP BY v.id_venta";
$sql .= " ORDER BY v.fecha_venta DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

jsonResponse([
  "success" => true,
  "data" => $stmt->fetchAll()
]);

