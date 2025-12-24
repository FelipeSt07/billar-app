<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id_producto'] ?? null;
$nombre = trim($data['nombre'] ?? '');
$precio = $data['precio'] ?? null;
$stock = $data['stock'] ?? null;

if (!$nombre || $precio === null || $stock === null) {
    jsonResponse(["success" => false, "message" => "Datos incompletos"], 400);
}

if ($id) {
    // EDITAR
    $stmt = $pdo->prepare("
        UPDATE productos 
        SET nombre = ?, precio = ?, stock = ?
        WHERE id_producto = ?
    ");
    $stmt->execute([$nombre, $precio, $stock, $id]);

    jsonResponse(["success" => true, "message" => "Producto actualizado"]);
} else {
    // INSERTAR
    $stmt = $pdo->prepare("
        INSERT INTO productos (nombre, precio, stock, activo)
        VALUES (?, ?, ?, 1)
    ");
    $stmt->execute([$nombre, $precio, $stock]);

    jsonResponse(["success" => true, "message" => "Producto agregado"]);
}
