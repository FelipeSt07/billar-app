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

  // 2️⃣ Insertar venta
  $stmt = $pdo->prepare("
    INSERT INTO ventas (tipo_venta, total, id_usuario)
    VALUES ('directa', ?, ?)
  ");
  $stmt->execute([$total, $_SESSION["id_usuario"]]);
  $idVenta = $pdo->lastInsertId();

  // 3️⃣ Detalles + stock + movimientos
  foreach ($productos as $p) {

  $idProducto = $p["id_producto"] ?? null;
  if (!$idProducto) {
    throw new Exception("ID de producto inválido");
  }

  // 1️⃣ Obtener precio y stock reales
    $stmt = $pdo->prepare("
      SELECT nombre, precio, stock, costo_promedio
      FROM productos
      WHERE id_producto = ?
      FOR UPDATE
    ");
    $stmt->execute([$idProducto]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
      throw new Exception("Producto no encontrado");
    }

    $cantidad = (int) $p["cantidad"];
    if ($cantidad <= 0) {
      throw new Exception(
        "Cantidad inválida para {$producto['nombre']}"
      );
    }
    $precio = (float) $producto["precio"];
    $subtotal = $precio * $cantidad;
    $costo_promedio = (float) $producto["costo_promedio"];

    $subtotal_costo = $cantidad * $costo_promedio;

    if ($precio <= $costo_promedio) {
      throw new Exception(
        "El precio de venta de {$producto['nombre']} es menor al costo"
      );
    }


    $total += $subtotal;

    // 2️⃣ Validar stock
    if ($producto["stock"] < $cantidad) {
      throw new Exception(
        "Stock insuficiente para {$producto['nombre']}"
      );
    }

    // Guardar detalle venta
    $stmt = $pdo->prepare("
      INSERT INTO detalle_venta
      (id_venta, id_producto, cantidad, precio_unitario, subtotal, costo_unitario, subtotal_costo)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
      $idVenta,
      $idProducto,
      $cantidad,
      $precio,
      $subtotal,
      $costo_promedio,
      $subtotal_costo
    ]);

    $nuevoStock = $producto["stock"] - $cantidad;
    $nuevoValorInventario = $nuevoStock * $costo_promedio;


    // 4️⃣ Actualizar stock
    $stmt = $pdo->prepare("
      UPDATE productos
      SET stock = ?, valor_inventario = ?
      WHERE id_producto = ?
    ");
    $stmt->execute([
      $nuevoStock,
      $nuevoValorInventario,
      $idProducto
    ]);

    // 5️⃣ Movimiento inventario
    $stmt = $pdo->prepare("
      INSERT INTO movimientos_inventario
      (id_producto, tipo, cantidad, motivo)
      VALUES (?, 'salida', ?, 'Venta directa')
    ");
    $stmt->execute([$idProducto, $cantidad]);
  }

  // 6️⃣ Actualizar total real de la venta
  $stmt = $pdo->prepare("
    UPDATE ventas
    SET total = ?
    WHERE id_venta = ?
  ");
  $stmt->execute([$total, $idVenta]);

  $pdo->commit();
  jsonResponse(["success" => true]);

} catch (Exception $e) {
  $pdo->rollBack();
  jsonResponse([
    "success" => false,
    "message" => $e->getMessage()
  ]);
}
