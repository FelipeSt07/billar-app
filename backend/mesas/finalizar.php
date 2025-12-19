<?php
require "../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);
$id_mesa = $data['id_mesa'] ?? null;

if (!$id_mesa) {
    echo json_encode(["success" => false, "message" => "ID mesa requerido"]);
    exit;
}

/* 1️⃣ Obtener sesión activa + tarifa */
$stmt = $pdo->prepare("
    SELECT 
        s.id_sesion,
        s.hora_inicio,
        t.precio_por_minuto
    FROM sesiones_mesa s
    JOIN mesas m ON m.id_mesa = s.id_mesa
    JOIN tarifas t ON t.id_tarifa = m.id_tarifa
    WHERE s.id_mesa = ? AND s.estado = 'activa'
    LIMIT 1
");
$stmt->execute([$id_mesa]);
$sesion = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sesion) {
    echo json_encode(["success" => false, "message" => "No hay sesión activa"]);
    exit;
}

/* 2️⃣ Calcular tiempo */
$inicio = new DateTime($sesion['hora_inicio']);
$fin = new DateTime();

$minutos = ceil(($fin->getTimestamp() - $inicio->getTimestamp()) / 60);
$costo_tiempo = $minutos * $sesion['precio_por_minuto'];

/* 3️⃣ Cerrar sesión */
$stmt = $pdo->prepare("
    UPDATE sesiones_mesa
    SET 
        estado = 'cerrada',
        hora_fin = NOW(),
        tiempo_total_minutos = ?,
        costo_tiempo = ?
    WHERE id_sesion = ?
");
$stmt->execute([$minutos, $costo_tiempo, $sesion['id_sesion']]);

/* 4️⃣ Liberar mesa */
$stmt = $pdo->prepare("
    UPDATE mesas SET estado = 'libre' WHERE id_mesa = ?
");
$stmt->execute([$id_mesa]);

echo json_encode([
    "success" => true,
    "message" => "Mesa finalizada",
    "tiempo_minutos" => $minutos,
    "costo_tiempo" => $costo_tiempo
]);
