<?php
require "../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);
$id_mesa = $data['id_mesa'] ?? null;

if (!$id_mesa) {
    echo json_encode(["success" => false, "message" => "ID mesa requerido"]);
    exit;
}

/* Pausar sesión activa */
$stmt = $pdo->prepare("
    UPDATE sesiones_mesa 
    SET estado = 'pausada' 
    WHERE id_mesa = ? AND estado = 'activa'
");
$stmt->execute([$id_mesa]);

/* Cambiar estado mesa */
$stmt = $pdo->prepare("
    UPDATE mesas SET estado = 'pausada' WHERE id_mesa = ?
");
$stmt->execute([$id_mesa]);

echo json_encode(["success" => true, "message" => "Mesa pausada"]);
