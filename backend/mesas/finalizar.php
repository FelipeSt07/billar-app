<?php
require_once "../middlewares/auth.php";
require_once "../config/database.php";
require_once "../utils/response.php";

$data = json_decode(file_get_contents("php://input"), true);

// Obtener sesión activa
$stmt = $pdo->prepare("
    SELECT s.id_sesion, s.hora_inicio, t.precio_por_minuto
    FROM sesiones_mesa s
    JOIN mesas m ON s.id_mesa = m.id_mesa
    JOIN tarifas t ON m.id_tarifa = t.id_tarifa
    WHERE s.id_mesa = ? AND s.estado = 'activa'
");
$stmt->execute([$data['id_mesa']]);
$sesion = $stmt->fetch();

if (!$sesion) {
    jsonResponse(["success" => false, "message" => "No hay sesión activa"], 400);
}

$inicio = new DateTime($sesion['hora_inicio']);
$fin = new DateTime();
$minutos = ceil(($fin->getTimestamp() - $inicio->getTimestamp()) / 60);
$costo = $minutos * $sesion['precio_por_minuto'];

// Cerrar sesión
$pdo->prepare("
    UPDATE sesiones_mesa
    SET hora_fin = NOW(),
        tiempo_total_minutos = ?,
        costo_tiempo = ?,
        estado = 'cerrada'
    WHERE id_sesion = ?
")->execute([$minutos, $costo, $sesion['id_sesion']]);

// Liberar mesa
$pdo->prepare("
    UPDATE mesas SET estado = 'libre'
    WHERE id_mesa = ?
")->execute([$data['id_mesa']]);

jsonResponse([
    "success" => true,
    "minutos" => $minutos,
    "costo" => $costo
]);
