<?php
require "../config/database.php";

$stmt = $pdo->query("
    SELECT 
        id_producto,
        nombre,
        precio,
        stock,
        categoria,
        activo
    FROM productos
    ORDER BY nombre
");

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "data" => $productos
]);
