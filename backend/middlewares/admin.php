<?php
require_once "auth.php";

if ($_SESSION['usuario']['rol'] !== 'admin') {
    jsonResponse([
        "success" => false,
        "message" => "Acceso denegado"
    ], 403);
}
