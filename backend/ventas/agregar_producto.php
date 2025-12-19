<?php
require "../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

$id_sesion   = $data['id_sesion'] ?? null;
$id_producto = $data['id_producto'] ?? null;
$cantidad    = $data['cantidad'] ?? null;

if (empty($id_sesion) || empty($id_producto) || empty($cantidad)) {
    echo json_encode([
        "success" => false,
        "message" => "Datos incompletos"
    ]);
    exit;
}

/* 1️⃣ Verificar sesión activa */
$stmt = $pdo->prepare("
    SELECT id_sesion 
    FROM sesiones_mesa 
    WHERE id_sesion = ? AND estado = 'activa'
");
$stmt->execute([$id_sesion]);
$sesion = $stmt->fetch();

if (!$sesion) {
    echo json_encode([
        "success" => false,
        "message" => "Sesión no válida o cerrada"
    ]);
    exit;
}

/* 2️⃣ Obtener producto */
$stmt = $pdo->prepare("
    SELECT precio, stock 
    FROM productos 
    WHERE id_producto = ? AND activo = 1
");
$stmt->execute([$id_producto]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    echo json_encode([
        "success" => false,
        "message" => "Producto no existe"
    ]);
    exit;
}

if ($producto['stock'] < $cantidad) {
    echo json_encode([
        "success" => false,
        "message" => "Stock insuficiente"
    ]);
    exit;
}

$precio   = $producto['precio'];
$subtotal = $precio * $cantidad;

/* 3️⃣ Crear venta si no existe */
$stmt = $pdo->prepare("
    SELECT id_venta 
    FROM ventas 
    WHERE id_sesion = ?
");
$stmt->execute([$id_sesion]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    $stmt = $pdo->prepare("
        INSERT INTO ventas (id_sesion, total, metodo_pago, id_usuario)
        VALUES (?, 0, 'efectivo', 1)
    ");
    $stmt->execute([$id_sesion]);
    $id_venta = $pdo->lastInsertId();
} else {
    $id_venta = $venta['id_venta'];
}

/* 4️⃣ Insertar detalle */
$stmt = $pdo->prepare("
    INSERT INTO detalle_venta 
    (id_venta, id_producto, cantidad, precio_unitario, subtotal)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([
    $id_venta,
    $id_producto,
    $cantidad,
    $precio,
    $subtotal
]);

/* 5️⃣ Actualizar stock */
$stmt = $pdo->prepare("
    UPDATE productos 
    SET stock = stock - ? 
    WHERE id_producto = ?
");
$stmt->execute([$cantidad, $id_producto]);

/* 6️⃣ Actualizar total venta */
$stmt = $pdo->prepare("
    UPDATE ventas 
    SET total = total + ? 
    WHERE id_venta = ?
");
$stmt->execute([$subtotal, $id_venta]);

echo json_encode([
    "success" => true,
    "message" => "Producto agregado correctamente",
    "id_venta" => $id_venta,
    "subtotal" => $subtotal
]);
