<?php
require "../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);
$id_mesa = $data['id_mesa'] ?? null;

if (!$id_mesa) {
    echo json_encode([
        "success" => false,
        "message" => "ID de mesa requerido"
    ]);
    exit;
}

/* 1️⃣ Verificar si ya existe una sesión activa para esta mesa */
$stmt = $pdo->prepare("
    SELECT id_sesion 
    FROM sesiones_mesa 
    WHERE id_mesa = ? AND estado = 'activa'
    LIMIT 1
");
$stmt->execute([$id_mesa]);
$sesion = $stmt->fetch(PDO::FETCH_ASSOC);

if ($sesion) {
    // ⚠️ Ya hay sesión activa → NO crear otra
    echo json_encode([
        "success" => true,
        "message" => "Mesa ya tiene sesión activa",
        "id_sesion" => $sesion['id_sesion']
    ]);
    exit;
}

/* 2️⃣ Crear nueva sesión */
$stmt = $pdo->prepare("
    INSERT INTO sesiones_mesa (id_mesa, hora_inicio, estado)
    VALUES (?, NOW(), 'activa')
");
$stmt->execute([$id_mesa]);

$id_sesion = $pdo->lastInsertId();

/* 3️⃣ Marcar mesa como ocupada */
$stmt = $pdo->prepare("
    UPDATE mesas SET estado = 'ocupada' WHERE id_mesa = ?
");
$stmt->execute([$id_mesa]);

echo json_encode([
    "success" => true,
    "message" => "Mesa iniciada correctamente",
    "id_sesion" => $id_sesion
]);
