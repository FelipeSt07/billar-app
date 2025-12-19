<?php
require "../config/database.php";

/* 1️⃣ Ventas del día */
$stmt = $pdo->query("
    SELECT COUNT(*) 
    FROM ventas
    WHERE DATE(fecha_venta) = CURDATE()
");
$ventas_hoy = $stmt->fetchColumn();

/* 2️⃣ Total vendido hoy */
$stmt = $pdo->query("
    SELECT IFNULL(SUM(total), 0)
    FROM ventas
    WHERE DATE(fecha_venta) = CURDATE()
");
$total_hoy = $stmt->fetchColumn();

/* 3️⃣ Mesas ocupadas */
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM mesas
    WHERE estado = 'ocupada'
");
$mesas_ocupadas = $stmt->fetchColumn();

/* 4️⃣ Productos con stock bajo */
$stmt = $pdo->query("
    SELECT COUNT(*)
    FROM productos
    WHERE stock <= 5 AND activo = 1
");
$stock_bajo = $stmt->fetchColumn();

echo json_encode([
    "success" => true,
    "data" => [
        "ventas_hoy" => (int)$ventas_hoy,
        "total_hoy" => (float)$total_hoy,
        "mesas_ocupadas" => (int)$mesas_ocupadas,
        "productos_stock_bajo" => (int)$stock_bajo
    ]
]);
