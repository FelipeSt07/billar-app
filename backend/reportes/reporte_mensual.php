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
    (
      SELECT IFNULL(SUM(total), 0)
      FROM ventas
      WHERE estado = 'activa'
        AND YEAR(fecha_venta) = ?
        AND MONTH(fecha_venta) = ?
    ) AS ingresos,

    (
      SELECT IFNULL(SUM(subtotal_costo), 0)
      FROM detalle_venta d
      JOIN ventas v ON v.id_venta = d.id_venta
      WHERE v.estado = 'activa'
        AND YEAR(v.fecha_venta) = ?
        AND MONTH(v.fecha_venta) = ?
    ) AS costo_ventas,

    (
      SELECT IFNULL(SUM(subtotal - subtotal_costo), 0)
      FROM detalle_venta d
      JOIN ventas v ON v.id_venta = d.id_venta
      WHERE v.estado = 'activa'
        AND YEAR(v.fecha_venta) = ?
        AND MONTH(v.fecha_venta) = ?
    ) AS utilidad
    ";

  $stmt = $pdo->prepare($sqlResumen);
  $stmt->execute([
    $anio, $mes,   // ingresos
    $anio, $mes,   // costo_ventas
    $anio, $mes    // utilidad
  ]);
  $resumen = $stmt->fetch();

  /* ==========================
     DETALLE DIARIO
  ========================== */
  $sqlDiario = "
    SELECT
      DATE(v.fecha_venta) AS dia,
      SUM(DISTINCT v.total) AS ingresos,
      SUM(d.subtotal - d.subtotal_costo) AS utilidad
    FROM ventas v
    JOIN detalle_venta d ON d.id_venta = v.id_venta
    WHERE v.estado = 'activa'
      AND YEAR(v.fecha_venta) = ?
      AND MONTH(v.fecha_venta) = ?
    GROUP BY v.id_venta, DATE(v.fecha_venta)
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
