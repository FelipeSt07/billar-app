<?php
require "../config/database.php";

$id_mesa = $_GET['id_mesa'] ?? null;

if (!$id_mesa) {
    echo json_encode(["success" => false]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        id_sesion,
        hora_inicio,
        estado,
        TIMESTAMPDIFF(MINUTE, hora_inicio, NOW()) AS minutos
    FROM sesiones_mesa
    WHERE id_mesa = ? AND estado != 'cerrada'
    ORDER BY id_sesion DESC
    LIMIT 1
");
$stmt->execute([$id_mesa]);
$sesion = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "data" => $sesion
]);
