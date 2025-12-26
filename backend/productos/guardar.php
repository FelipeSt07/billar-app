<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id_producto'] ?? null;
$nombre = trim($data['nombre'] ?? '');
$precio = $data['precio'] ?? null;
$stock = $data['stock'] ?? null;
$categoria = $data['categoria'] ?? null;

if (!$nombre || $precio === null || $stock === null || !$categoria) {
    jsonResponse(["success" => false, "message" => "Datos incompletos"], 400);
}

$pdo->beginTransaction();

try {

    if ($id) {
        // 1️⃣ Obtener stock actual
        $stmt = $pdo->prepare("
            SELECT stock 
            FROM productos 
            WHERE id_producto = ?
        ");
        $stmt->execute([$id]);
        $producto = $stmt->fetch();

        if (!$producto) {
            throw new Exception("Producto no existe");
        }

        $stockAnterior = (int)$producto['stock'];
        $stockNuevo = (int)$stock;
        $diferencia = $stockNuevo - $stockAnterior;

        // 2️⃣ Actualizar producto
        $stmt = $pdo->prepare("
            UPDATE productos 
            SET nombre = ?, precio = ?, stock = ?, categoria = ?
            WHERE id_producto = ?
        ");
        $stmt->execute([$nombre, $precio, $stockNuevo, $categoria, $id]);

        // 3️⃣ Registrar movimiento si cambió el stock
        if ($diferencia !== 0) {
            $tipo = $diferencia > 0 ? 'entrada' : 'salida';
            $cantidad = abs($diferencia);

            $stmt = $pdo->prepare("
                INSERT INTO movimientos_inventario
                (id_producto, tipo, cantidad, motivo)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $id,
                $tipo,
                $cantidad,
                'Ajuste manual de inventario'
            ]);
        }

        $pdo->commit();
        jsonResponse(["success" => true, "message" => "Producto actualizado"]);

    } else {
        // 1️⃣ Insertar producto
        $stmt = $pdo->prepare("
            INSERT INTO productos (nombre, precio, stock, categoria, activo)
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->execute([$nombre, $precio, $stock, $categoria]);

        $idProducto = $pdo->lastInsertId();

        // 2️⃣ Registrar stock inicial
        if ($stock > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO movimientos_inventario
                (id_producto, tipo, cantidad, motivo)
                VALUES (?, 'entrada', ?, 'Stock inicial')
            ");
            $stmt->execute([$idProducto, $stock]);
        }

        $pdo->commit();
        jsonResponse(["success" => true, "message" => "Producto agregado"]);
    }

} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse([
        "success" => false,
        "message" => $e->getMessage()
    ], 500);
}
