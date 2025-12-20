<?php
require "../config/database.php";

$id_mesa = $_GET['id_mesa'] ?? null;

if (!$id_mesa) {
  echo json_encode(["success" => false, "message" => "Mesa no válida"]);
  exit;
}

$stmt = $pdo->prepare("
  SELECT 
    s.id_sesion,
    s.estado,
    s.hora_inicio,

    -- ⏱ minutos reales
    TIMESTAMPDIFF(
      MINUTE,
      s.hora_inicio,
      IF(s.estado = 'pausada', s.hora_pausa, NOW())
    ) AS minutos,

    -- 🧾 total productos
    IFNULL(SUM(vd.subtotal), 0) AS total_productos

  FROM sesiones_mesa s
  LEFT JOIN ventas v 
    ON v.id_sesion = s.id_sesion
  LEFT JOIN ventas_detalle vd 
    ON vd.id_venta = v.id_venta

  WHERE s.id_mesa = ?
    AND s.estado != 'cerrada'

  GROUP BY s.id_sesion
  ORDER BY s.id_sesion DESC
  LIMIT 1
");

$stmt->execute([$id_mesa]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
  "success" => true,
  "data" => $data
]);
