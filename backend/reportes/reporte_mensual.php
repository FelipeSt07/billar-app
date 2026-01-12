<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

/*
  Se espera:
  ?anio=YYYY&mes=MM
*/

$anio = $_GET['anio'] ?? null;
$mes  = $_GET['mes'] ?? null;

if (!$anio || !$mes) {
  jsonResponse([
    "success" => false,
    "message" => "Año y mes requeridos"
  ], 400);
  exit;
}

try {

  /* ==========================
     RESUMEN MENSUAL
  ========================== */
  $sqlResumen = "
    SELECT
      IFNULL(SUM(v.total), 0) AS ingresos,
      IFNULL(SUM(d.subtotal_costo), 0) AS costo_ventas,
      IFNULL(SUM(d.subtotal - d.subtotal_costo), 0) AS utilidad
    FROM ventas v
    JOIN detalle_venta d ON d.id_venta = v.id_venta
    WHERE v.estado = 'activa'
      AND YEAR(v.fecha_venta) = ?
      AND MONTH(v.fecha_venta) = ?
      ";

  $stmt = $pdo->prepare($sqlResumen);
  $stmt->execute([
    $anio, $mes,
  ]);
  $resumen = $stmt->fetch();

  /* ==========================
     DETALLE DIARIO
  ========================== */
  $sqlDiario = "
    SELECT
      DATE(v.fecha_venta) AS dia,
      SUM(v.total) AS ingresos,
      SUM(d.subtotal - d.subtotal_costo) AS utilidad
    FROM ventas v
    JOIN detalle_venta d ON d.id_venta = v.id_venta
    WHERE v.estado = 'activa'
      AND YEAR(v.fecha_venta) = ?
      AND MONTH(v.fecha_venta) = ?
    GROUP BY DATE(v.fecha_venta)
    ORDER BY dia ASC
  ";

  $stmt = $pdo->prepare($sqlDiario);
  $stmt->execute([$anio, $mes]);
  $diario = $stmt->fetchAll();

  /* ==========================
     RESPUESTA
  ========================== */
  jsonResponse([
    "success" => true,
    "resumen" => [
      "ingresos" => (float) $resumen["ingresos"],
      "gastos"   => (float) $resumen["costo_ventas"],
      "utilidad" => (float) $resumen["utilidad"]
    ],
    "diario" => $diario
  ]);

} catch (Exception $e) {

  jsonResponse([
    "success" => false,
    "message" => "Error al generar el reporte mensual"
  ], 500);

}
