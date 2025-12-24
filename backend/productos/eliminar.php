<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

/* Solo admin puede eliminar */
if ($_SESSION['rol'] !== 'admin') {
    jsonResponse([
        "success" => false,
        "message" => "No autorizado"
    ], 403);
}

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id_producto'] ?? null;

if (!$id || !is_numeric($id)) {
    jsonResponse([
        "success" => false,
        "message" => "ID inválido"
    ], 400);
}

$stmt = $pdo->prepare("
    UPDATE productos
    SET activo = 0
    WHERE id_producto = ?
");

$stmt->execute([$id]);

jsonResponse([
    "success" => true,
    "message" => "Producto eliminado correctamente"
]);
