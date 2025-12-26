<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$data = json_decode(file_get_contents("php://input"), true);
$productos = $data["productos"] ?? [];

if (empty($productos)) {
  jsonResponse(["success" => false, "message" => "Carrito vacío"]);
}

try {
  $pdo->beginTransaction();

  // 1️⃣ Calcular total
  $total = 0;
  foreach ($productos as $p) {
    $total += $p["precio"] * $p["cantidad"];
  }

  // 2️⃣ Insertar venta
  $stmt = $pdo->prepare("
    INSERT INTO ventas (tipo_venta, total, id_usuario)
    VALUES ('directa', ?, ?)
  ");
  $stmt->execute([$total, $_SESSION["id_usuario"]]);
  $idVenta = $pdo->lastInsertId();

  // 3️⃣ Detalles + stock + movimientos
  foreach ($productos as $p) {

  // 1️⃣ Obtener precio y stock reales
    $stmt = $pdo->prepare("
      SELECT nombre, precio, stock
      FROM productos
      WHERE id_producto = ?
      FOR UPDATE
    ");
    $stmt->execute([$p["id"]]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
      throw new Exception("Producto no encontrado");
    }

    $cantidad = (int) $p["cantidad"];
    $precio = (float) $producto["precio"];
    $subtotal = $precio * $cantidad;

    // 2️⃣ Validar stock
    if ($producto["stock"] < $cantidad) {
      throw new Exception(
        "Stock insuficiente para {$producto['nombre']}"
      );
    }

    // Guardar detalle venta
    $stmt = $pdo->prepare("
      INSERT INTO detalle_venta
      (id_venta, id_producto, cantidad, precio_unitario, subtotal)
      VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
      $idVenta,
      $p["id"],
      $cantidad,
      $precio,
      $subtotal
    ]);

    // 4️⃣ Actualizar stock
    $stmt = $pdo->prepare("
      UPDATE productos
      SET stock = stock - ?
      WHERE id_producto = ?
    ");
    $stmt->execute([$cantidad, $p["id"]]);

    // 5️⃣ Movimiento inventario
    $stmt = $pdo->prepare("
      INSERT INTO movimientos_inventario
      (id_producto, tipo, cantidad, motivo)
      VALUES (?, 'salida', ?, 'Venta directa')
    ");
    $stmt->execute([$p["id"], $cantidad]);
  }

  $pdo->commit();
  jsonResponse(["success" => true]);

} catch (Exception $e) {
  $pdo->rollBack();
  jsonResponse([
    "success" => false,
    "message" => $e->getMessage()
  ]);
}
