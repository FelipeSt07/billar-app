<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$idCompra = $_GET["id_compra"] ?? null;

if (!$idCompra) {
  jsonResponse([
    "success" => false,
    "message" => "ID de compra requerido"
  ], 400);
}

$stmt = $pdo->prepare("
  SELECT
    p.nombre,
    d.cantidad,
    d.costo_unitario,
    d.subtotal
  FROM detalle_compra d
  JOIN productos p ON p.id_producto = d.id_producto
  WHERE d.id_compra = ?
");

$stmt->execute([$idCompra]);

$detalle = $stmt->fetchAll();

jsonResponse([
  "success" => true,
  "data" => $detalle
]);
