<?php
session_start();

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once "conexion.php";
$mysqli = conexionBBDD();

$user_id = $_SESSION['user_id'] ?? null;
$rol     = $_SESSION['rol']     ?? null;

if (!$user_id || $rol !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// GET: Listar todos los txandalaris
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $mysqli->prepare("SELECT id, nombre, dni, email, direccion FROM usuarios WHERE rol = 'txandalari' ORDER BY nombre ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    $txandalaris = [];
    while ($row = $result->fetch_assoc()) {
        $txandalaris[] = $row;
    }
    echo json_encode(['success' => true, 'txandalaris' => $txandalaris]);
    exit;
}

// PATCH: Cambiar rol de txandalari a socio
if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id_usuario = intval($data['id_usuario'] ?? 0);

    if (!$id_usuario) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    // Verificamos que realmente es txandalari antes de cambiar
    $stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE id = ? AND rol = 'txandalari'");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado o no es txandalari']);
        exit;
    }

    $stmt = $mysqli->prepare("UPDATE usuarios SET rol = 'socio' WHERE id = ? AND rol = 'txandalari'");
    $stmt->bind_param("i", $id_usuario);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Rol actualizado a socio']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
    }
    exit;
}

cerrarConexion($mysqli);
?>