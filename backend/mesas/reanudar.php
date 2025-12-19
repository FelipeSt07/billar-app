<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$pdo->prepare("
    UPDATE mesas SET estado = 'ocupada'
    WHERE id_mesa = ?
")->execute([$data['id_mesa']]);

$pdo->prepare("
    UPDATE sesiones_mesa
    SET estado = 'activa'
    WHERE id_mesa = ? AND estado = 'pausada'
")->execute([$data['id_mesa']]);

jsonResponse(["success" => true, "message" => "Mesa reanudada"]);
