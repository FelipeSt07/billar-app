<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$data = json_decode(file_get_contents("php://input"), true);
$idCompra = $data["id_compra"] ?? null;

if (!$idCompra) {
  jsonResponse(["success" => false, "message" => "ID inválido"]);
  exit;
}

$stmt = $pdo->prepare("
  SELECT estado
  FROM compras
  WHERE id_compra = ?
");
$stmt->execute([$idCompra]);
$compra = $stmt->fetch();

if (!$compra) {
  jsonResponse([
    "success" => false,
    "message" => "La compra no existe"
  ]);
  exit;
}

if ($compra["estado"] === "anulada") {
  jsonResponse([
    "success" => false,
    "message" => "La compra ya fue anulada"
  ]);
  exit;
}

$pdo->beginTransaction();

try {

  // 1️⃣ Obtener detalle de la compra
  $stmt = $pdo->prepare("
    SELECT id_producto, cantidad, costo_unitario
    FROM detalle_compra
    WHERE id_compra = ?
  ");
  $stmt->execute([$idCompra]);
  $detalles = $stmt->fetchAll();

  if (!$detalles) {
    throw new Exception("La compra no tiene detalles");
  }


  foreach ($detalles as $d) {

    // 2️⃣ Estado actual del producto
    $stmt = $pdo->prepare("
      SELECT stock, costo_promedio, valor_inventario
      FROM productos
      WHERE id_producto = ?
      FOR UPDATE
    ");
    $stmt->execute([$d["id_producto"]]);
    $p = $stmt->fetch();

    $stockActual = $p["stock"];
    $valorActual = $p["valor_inventario"];

    $q = $d["cantidad"];
    $c = $d["costo_unitario"];

    // 🚨 VALIDACIÓN CRÍTICA
    if ($stockActual < $q) {
      throw new Exception(
        "No se puede anular la compra: el producto ya fue vendido"
      );
    }

    // 3️⃣ Reversión correcta
    $nuevoStock = $stockActual - $q;
    $nuevoValor = $valorActual - ($q * $c);

    if ($nuevoStock > 0) {
      $nuevoCosto = $nuevoValor / $nuevoStock;
    } else {
      $nuevoCosto = 0;
      $nuevoValor = 0;
    }

    // 4️⃣ Actualizar producto
    $stmt = $pdo->prepare("
      UPDATE productos
      SET stock = ?, costo_promedio = ?, valor_inventario = ?
      WHERE id_producto = ?
    ");
    $stmt->execute([
      $nuevoStock,
      round($nuevoCosto, 2),
      round($nuevoValor, 2),
      $d["id_producto"]
    ]);

    // 5️⃣ Registrar movimiento de inventario (ANULACIÓN DE COMPRA)
    $stmt = $pdo->prepare("
    INSERT INTO movimientos_inventario
    (id_producto, tipo, cantidad, motivo)
    VALUES (?, 'salida', ?, 'Anulación de compra')
    ");

    $stmt->execute([
    $d["id_producto"],
    $q
    ]);
  }

  // 5️⃣ Marcar compra como anulada
  $stmt = $pdo->prepare("
    UPDATE compras
    SET estado = 'anulada'
    WHERE id_compra = ?
  ");
  $stmt->execute([$idCompra]);

  $pdo->commit();

  jsonResponse([
    "success" => true,
    "message" => "Compra anulada correctamente"
  ]);

} catch (Exception $e) {

  $pdo->rollBack();

  jsonResponse([
    "success" => false,
    "message" => $e->getMessage()
  ]);
}
