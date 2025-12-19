<?php
session_start();
session_destroy();

require_once "../utils/response.php";

jsonResponse([
    "success" => true,
    "message" => "Sesión cerrada correctamente"
]);
