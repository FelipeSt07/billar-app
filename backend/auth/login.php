<?php
require "../config/database.php";
session_start();

$data = json_decode(file_get_contents("php://input"), true);
$usuario = $data['usuario'] ?? null;
$password = $data['password'] ?? null;

if (!$usuario || !$password) {
    echo json_encode(["success"=>false,"message"=>"Datos incompletos"]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ?");
$stmt->execute([$usuario]);
$u = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$u || !password_verify($password, $u['password_hash'])) {
    echo json_encode(["success"=>false,"message"=>"Usuario o contraseña incorrectos"]);
    exit;
}

/* Guardar sesión */
$_SESSION['id_usuario'] = $u['id_usuario'];
$_SESSION['rol'] = $u['rol'];

echo json_encode(["success"=>true,"usuario"=>$u['usuario'],"rol"=>$u['rol']]);
