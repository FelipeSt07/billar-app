<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$inicio = $_GET["inicio"] ?? null;
$fin = $_GET["fin"] ?? null;

$sql = "
  SELECT
    c.id_compra,
    c.fecha,
    c.proveedor,
    c.total,
    c.estado,
    u.nombre AS usuario
  FROM compras c
  JOIN usuarios u ON u.id_usuario = c.id_usuario
  WHERE 1
";

$params = [];

if ($inicio && $fin) {
  $sql .= " AND DATE(c.fecha) BETWEEN ? AND ?";
  $params[] = $inicio;
  $params[] = $fin;
}

$sql .= " ORDER BY c.fecha DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

jsonResponse([
  "success" => true,
  "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
