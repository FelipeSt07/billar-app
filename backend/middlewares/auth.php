<?php
session_start();

require_once __DIR__ . "/../utils/response.php";

if (!isset($_SESSION['usuario'])) {
    jsonResponse([
        "success" => false,
        "message" => "No autenticado"
    ], 401);
}
