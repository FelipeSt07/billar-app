<?php
session_start();
require_once "../utils/response.php";

if (!isset($_SESSION['usuario'])) {
    jsonResponse(["logged" => false]);
}

jsonResponse([
    "logged" => true,
    "usuario" => $_SESSION['usuario']
]);
