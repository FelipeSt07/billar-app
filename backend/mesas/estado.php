<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$stmt = $pdo->query("
    SELECT 
        m.id_mesa,
        m.nombre_mesa,
        m.estado,
        s.hora_inicio
    FROM mesas m
    LEFT JOIN sesiones_mesa s
      ON m.id_mesa = s.id_mesa
      AND s.estado = 'activa'
");

jsonResponse([
    "success" => true,
    "data" => $stmt->fetchAll()
]);
