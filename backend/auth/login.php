<?php
session_start();

require_once "../config/database.php";
require_once "../utils/response.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['usuario'], $data['password'])) {
    jsonResponse([
        "success" => false,
        "message" => "Datos incompletos"
    ], 400);
}

$stmt = $pdo->prepare("
    SELECT id_usuario, nombre, password_hash, rol, activo
    FROM usuarios
    WHERE usuario = ?
    LIMIT 1
");

$stmt->execute([$data['usuario']]);
$usuario = $stmt->fetch();

if (!$usuario || !$usuario['activo']) {
    jsonResponse([
        "success" => false,
        "message" => "Usuario no válido"
    ], 401);
}

if (!password_verify($data['password'], $usuario['password_hash'])) {
    jsonResponse([
        "success" => false,
        "message" => "Credenciales incorrectas"
    ], 401);
}

// Guardar sesión
$_SESSION['usuario'] = [
    "id"   => $usuario['id_usuario'],
    "nombre" => $usuario['nombre'],
    "rol"  => $usuario['rol']
];

jsonResponse([
    "success" => true,
    "message" => "Login exitoso",
    "usuario" => $_SESSION['usuario']
]);
