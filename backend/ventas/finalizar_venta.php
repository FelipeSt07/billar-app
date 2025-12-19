<?php
require __DIR__ . "/../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

$id_sesion   = $data['id_sesion'] ?? null;
$metodo_pago = $data['metodo_pago'] ?? null;

if (empty($id_sesion) || empty($metodo_pago)) {
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ]);
    exit;
}

/* 1️⃣ Verificar venta existente */
$stmt = $pdo->prepare("
    SELECT id_venta, total 
    FROM ventas 
    WHERE id_sesion = ?
");
$stmt->execute([$id_sesion]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    echo json_encode([
        "success" => false,
        "message" => "No existe venta para esta sesión"
    ]);
    exit;
}

/* 2️⃣ Actualizar método de pago */
$stmt = $pdo->prepare("
    UPDATE ventas 
    SET metodo_pago = ? 
    WHERE id_venta = ?
");
$stmt->execute([$metodo_pago, $venta['id_venta']]);

/* 3️⃣ Cerrar sesión de mesa */
$stmt = $pdo->prepare("
    UPDATE sesiones_mesa
    SET estado = 'cerrada',
        hora_fin = NOW()
    WHERE id_sesion = ?
");
$stmt->execute([$id_sesion]);

echo json_encode([
    "success" => true,
    "message" => "Venta finalizada correctamente",
    "id_venta" => $venta['id_venta'],
    "total" => $venta['total'],
    "metodo_pago" => $metodo_pago
]);
