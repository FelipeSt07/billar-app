<?php
require "../config/database.php";

$stmt = $pdo->query("
    SELECT 
        m.id_mesa,
        m.nombre_mesa,
        m.estado,
        t.nombre_tarifa,
        t.precio_por_hora
    FROM mesas m
    INNER JOIN tarifas t ON m.id_tarifa = t.id_tarifa
");

$mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "data" => $mesas
]);
