<?php
session_start();

// 1️⃣ Permitir CORS
header("Access-Control-Allow-Origin: http://localhost:5173"); // tu frontend
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// 2️⃣ Responder a preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 3️⃣ Conectar a la DB
require 'db.php';

// 4️⃣ Leer JSON
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['usuario_id'] ?? 0;
$mensaje = $data['mensaje'] ?? '';

// 5️⃣ Validar
if (!$user_id) {
    echo json_encode(['ok'=>false, 'message'=>'ID de usuario no proporcionado']);
    exit;
}

// 6️⃣ Guardar solicitud
try {
    $stmt = $db->prepare("INSERT INTO solicitudes_txandalari (user_id, mensaje, fecha) VALUES (?, ?, NOW())");
    $ok = $stmt->execute([$user_id, $mensaje]);

    if ($ok) {
        echo json_encode(['ok'=>true, 'message'=>'Solicitud enviada correctamente']);
    } else {
        echo json_encode(['ok'=>false, 'message'=>'Error al guardar la solicitud']);
    }
} catch (PDOException $e) {
    echo json_encode(['ok'=>false, 'message'=>$e->getMessage()]);
}
?>