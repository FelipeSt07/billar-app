<?php
require_once "../config/database.php";

$id_mesa = $_GET['id_mesa'] ?? null;

if (!$id_mesa) {
    echo json_encode([
        "success" => false,
        "message" => "ID de mesa requerido"
    ]);
    exit;
}

/* Buscar sesión activa de la mesa */
$stmt = $pdo->prepare("
    SELECT 
        id_sesion,
        hora_inicio,
        estado
    FROM sesiones_mesa
    WHERE id_mesa = ? AND estado = 'activa'
    LIMIT 1
");
$stmt->execute([$id_mesa]);
$sesion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sesion) {
    echo json_encode([
        "success" => false,
        "message" => "No hay sesión activa para esta mesa"
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "id_sesion" => $sesion['id_sesion'],
    "hora_inicio" => $sesion['hora_inicio'],
    "estado" => $sesion['estado']
]);
