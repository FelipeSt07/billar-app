<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id_producto'] ?? null;
$nombre = trim($data['nombre'] ?? '');
$precio = $data['precio'] ?? 0;
$categoria = $data['categoria'] ?? null;
$activo = $data['activo'] ?? 1;

if (!$nombre || !$categoria) {
    jsonResponse([
        "success" => false,
        "message" => "Nombre y categoría son obligatorios"
    ], 400);
}

try {

    if ($id) {
        // 🟡 EDITAR PRODUCTO (MÓDULO PRODUCTOS)
        $stmt = $pdo->prepare("
            UPDATE productos
            SET nombre = ?, precio = ?, categoria = ?, activo = ?
            WHERE id_producto = ?
        ");
        $stmt->execute([
            $nombre,
            $precio,
            $categoria,
            $activo,
            $id
        ]);

        jsonResponse([
            "success" => true,
            "message" => "Producto actualizado correctamente"
        ]);

    } else {
        // 🟢 CREAR PRODUCTO (DESDE COMPRAS O PRODUCTOS)
        $stmt = $pdo->prepare("
            INSERT INTO productos 
            (nombre, precio, categoria, stock, activo, costo_promedio, valor_inventario)
            VALUES (?, ?, ?, 0, 1, 0, 0)
        ");
        $stmt->execute([
            $nombre,
            $precio,
            $categoria
        ]);

        jsonResponse([
            "success" => true,
            "id_producto" => $pdo->lastInsertId(),
            "message" => "Producto creado correctamente"
        ]);
    }

} catch (Exception $e) {
    jsonResponse([
        "success" => false,
        "message" => "Error al guardar el producto"
    ], 500);
}
