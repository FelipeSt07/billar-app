<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$stmt = $pdo->query("
  SELECT
    v.id_venta,
    v.fecha_venta,
    v.total,
    v.tipo_venta,
    v.estado,
    u.nombre AS usuario
  FROM ventas v
  JOIN usuarios u ON u.id_usuario = v.id_usuario
  ORDER BY v.fecha_venta DESC;

");

jsonResponse([
  "success" => true,
  "data" => $stmt->fetchAll()
]);
