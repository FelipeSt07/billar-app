<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$data = json_decode(file_get_contents("php://input"), true);

$pdo->prepare("
    UPDATE mesas SET estado = 'pausada'
    WHERE id_mesa = ?
")->execute([$data['id_mesa']]);

$pdo->prepare("
    UPDATE sesiones_mesa
    SET estado = 'pausada'
    WHERE id_mesa = ? AND estado = 'activa'
")->execute([$data['id_mesa']]);

jsonResponse(["success" => true, "message" => "Mesa pausada"]);
