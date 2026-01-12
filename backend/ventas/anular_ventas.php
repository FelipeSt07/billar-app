<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$data = json_decode(file_get_contents("php://input"), true);
$idVenta = $data["id_venta"] ?? null;

if (!$idVenta) {
  jsonResponse(["success" => false, "message" => "ID de venta requerido"]);
}

try {
  $pdo->beginTransaction();

  // 1️⃣ Bloquear venta
  $stmt = $pdo->prepare("
    SELECT estado
    FROM ventas
    WHERE id_venta = ?
    FOR UPDATE
  ");
  $stmt->execute([$idVenta]);
  $venta = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$venta) {
    throw new Exception("La venta no existe");
  }

  if ($venta["estado"] !== "activa") {
    throw new Exception("La venta ya fue anulada");
  }

  // 2️⃣ Obtener detalle completo
  $stmt = $pdo->prepare("
    SELECT id_producto, cantidad, costo_unitario, subtotal_costo
    FROM detalle_venta
    WHERE id_venta = ?
  ");
  $stmt->execute([$idVenta]);
  $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (empty($detalles)) {
    throw new Exception("La venta no tiene detalle");
  }

  // 3️⃣ Revertir inventario
  foreach ($detalles as $d) {

    // 🔒 Bloquear producto
    $stmt = $pdo->prepare("
      SELECT stock, valor_inventario
      FROM productos
      WHERE id_producto = ?
      FOR UPDATE
    ");
    $stmt->execute([$d["id_producto"]]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
      throw new Exception("Producto no encontrado");
    }

    $nuevoStock = $producto["stock"] + $d["cantidad"];
    $nuevoValorInventario = $producto["valor_inventario"] + $d["subtotal_costo"];

    // ✅ Restaurar stock y valor contable
    $stmt = $pdo->prepare("
      UPDATE productos
      SET stock = ?, valor_inventario = ?
      WHERE id_producto = ?
    ");
    $stmt->execute([
      $nuevoStock,
      $nuevoValorInventario,
      $d["id_producto"]
    ]);

    // Movimiento inventario
    $stmt = $pdo->prepare("
      INSERT INTO movimientos_inventario
      (id_producto, tipo, cantidad, motivo)
      VALUES (?, 'entrada', ?, 'Anulación de venta')
    ");
    $stmt->execute([
      $d["id_producto"],
      $d["cantidad"]
    ]);
  }

  // 4️⃣ Anular venta
  $stmt = $pdo->prepare("
    UPDATE ventas
    SET estado = 'anulada',
        fecha_anulacion = NOW()
    WHERE id_venta = ?
  ");
  $stmt->execute([$idVenta]);

  $pdo->commit();

  jsonResponse([
    "success" => true,
    "message" => "Venta anulada correctamente"
  ]);

} catch (Exception $e) {
  $pdo->rollBack();
  jsonResponse([
    "success" => false,
    "message" => $e->getMessage()
  ]);
}
