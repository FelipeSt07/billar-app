<?php
require_once "../config/database.php";
require_once "../utils/response.php";

$stmt = $pdo->query("
    SELECT 
        m.id_mesa,
        m.nombre_mesa,
        m.estado,
        t.nombre_tarifa,
        t.precio_por_hora
    FROM mesas m
    JOIN tarifas t ON m.id_tarifa = t.id_tarifa
");

$mesas = $stmt->fetchAll();

jsonResponse([
    "success" => true,
    "data" => $mesas
]);
