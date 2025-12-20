<?php
require "../config/database.php";

$id_mesa = $_GET['id_mesa'] ?? null;

$stmt = $pdo->prepare("
  SELECT *
  FROM sesiones_mesa
  WHERE id_mesa = ?
  AND estado IN ('activa','pausada')
  ORDER BY id_sesion DESC
  LIMIT 1
");
$stmt->execute([$id_mesa]);

$sesion = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
  "success" => true,
  "data" => $sesion
]);
