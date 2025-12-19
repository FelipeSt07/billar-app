<?php
session_start();

function login_required($roles_permitidos = []) {
    if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['rol'])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "No autenticado"]);
        exit;
    }

    if (!empty($roles_permitidos) && !in_array($_SESSION['rol'], $roles_permitidos)) {
        http_response_code(403);
        echo json_encode(["success" => false, "message" => "Permiso denegado"]);
        exit;
    }
}
