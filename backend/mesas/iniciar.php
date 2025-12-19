<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id_mesa'])) {
    jsonResponse(["success" => false, "message" => "Mesa no especificada"], 400);
}

// Verificar mesa
$stmt = $pdo->prepare("SELECT estado FROM mesas WHERE id_mesa = ?");
$stmt->execute([$data['id_mesa']]);
$mesa = $stmt->fetch();

if (!$mesa || $mesa['estado'] !== 'libre') {
    jsonResponse(["success" => false, "message" => "Mesa no disponible"], 400);
}

// Crear sesión
$pdo->prepare("
    INSERT INTO sesiones_mesa (id_mesa, hora_inicio)
    VALUES (?, NOW())
")->execute([$data['id_mesa']]);

// Actualizar mesa
$pdo->prepare("
    UPDATE mesas SET estado = 'ocupada'
    WHERE id_mesa = ?
")->execute([$data['id_mesa']]);

jsonResponse([
    "success" => true,
    "message" => "Mesa iniciada correctamente",
    "id_sesion" => $idSesion
]);
