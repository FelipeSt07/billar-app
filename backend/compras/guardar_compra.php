<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$data = json_decode(file_get_contents("php://input"), true);

$proveedor = trim($data['proveedor'] ?? '');
$fecha = $data['fecha'] ?? null;
$total = $data['total'] ?? 0;
$productos = $data['productos'] ?? [];
$id_usuario = $_SESSION['id_usuario'] ?? null;

if (!$fecha || empty($productos)) {
    jsonResponse([
        "success" => false,
        "message" => "Datos incompletos de la compra"
    ], 400);
}

$pdo->beginTransaction();

try {

    // 1️⃣ Insertar compra
    $stmt = $pdo->prepare("
        INSERT INTO compras (proveedor, fecha, total, id_usuario)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$proveedor, $fecha, $total, $id_usuario]);

    $idCompra = $pdo->lastInsertId();

    // 2️⃣ Procesar productos
    foreach ($productos as $p) {

        $idProducto = $p['id_producto'];
        $cantidad = (int)$p['cantidad'];
        $costo = (float)$p['costo_unitario'];
        $subtotal = $cantidad * $costo;

        if ($cantidad <= 0 || $costo < 0) {
            throw new Exception("Cantidad o costo inválido");
        }

        // 🔍 Obtener stock y costo promedio actual
        $stmt = $pdo->prepare("
            SELECT stock, costo_promedio
            FROM productos
            WHERE id_producto = ?
            FOR UPDATE
        ");
        $stmt->execute([$idProducto]);
        $producto = $stmt->fetch();

        if (!$producto) {
            throw new Exception("Producto no encontrado");
        }

        $stockActual = (int)$producto['stock'];
        $costoPromedioActual = (float)$producto['costo_promedio'];

        $nuevoStock = $stockActual + $cantidad;

        if ($nuevoStock > 0) {
            $nuevoCostoPromedio = (
                ($stockActual * $costoPromedioActual) +
                ($cantidad * $costo)
            ) / $nuevoStock;
        } else {
            $nuevoCostoPromedio = 0;
        }

        // 2.1 Detalle compra
        $stmt = $pdo->prepare("
            INSERT INTO detalle_compra
            (id_compra, id_producto, cantidad, costo_unitario, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $idCompra,
            $idProducto,
            $cantidad,
            $costo,
            $subtotal
        ]);

        // 2.2 Actualizar stock
        $stmt = $pdo->prepare("
            UPDATE productos
            SET stock = ?, costo_promedio = ?
            WHERE id_producto = ?
        ");
        $stmt->execute([
            $nuevoStock,
            $nuevoCostoPromedio,
            $idProducto
        ]);

        // 2.3 Movimiento inventario
        $stmt = $pdo->prepare("
            INSERT INTO movimientos_inventario
            (id_producto, tipo, cantidad, motivo)
            VALUES (?, 'entrada', ?, ?)
        ");
        $stmt->execute([
            $idProducto,
            $cantidad,
            'Compra'
        ]);
    }

    $pdo->commit();

    jsonResponse([
        "success" => true,
        "message" => "Compra registrada correctamente"
    ]);

} catch (Exception $e) {
    $pdo->rollBack();

    jsonResponse([
        "success" => false,
        "message" => $e->getMessage()
    ], 500);
}
