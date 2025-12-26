<?php
// require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$stmt = $pdo->query("
    SELECT id_producto, nombre, categoria, precio, stock
    FROM productos
    WHERE activo = 1
    ORDER BY stock ASC, nombre ASC
");

jsonResponse([
    "success" => true,
    "data" => $stmt->fetchAll()
]);
