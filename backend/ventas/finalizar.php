<?php
require "../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

$id_sesion = $data['id_sesion'] ?? null;
$metodo    = $data['metodo_pago'] ?? 'efectivo';

if (!$id_sesion) {
    echo json_encode([
        "success" => false,
        "message" => "ID sesión requerido"
    ]);
    exit;
}

$stmt = $pdo->prepare("
    UPDATE ventas
    SET metodo_pago = ?
    WHERE id_sesion = ?
");
$stmt->execute([$metodo, $id_sesion]);

echo json_encode([
    "success" => true,
    "message" => "Venta finalizada"
]);
