<?php
session_start();

// --- CORS UNIVERSAL (Funciona en local y en servidor) ---
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
header("Content-Type: application/json; charset=UTF-8");

require_once "conexion.php";
$mysqli = conexionBBDD();

// --- MAGIA: Borrar automáticamente solicitudes aceptadas de hace más de 1 mes ---
$mysqli->query("DELETE FROM solicitudes_artistas WHERE estado = 'aceptada' AND fecha_solicitud < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)");

// --- GET: OBTENER SOLICITUDES ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT * FROM solicitudes_artistas ORDER BY FIELD(estado, 'pendiente', 'aceptada'), fecha_solicitud DESC";
    $resultado = $mysqli->query($sql);
    
    $solicitudes = [];
    while ($fila = $resultado->fetch_assoc()) {
        $solicitudes[] = $fila;
    }
    echo json_encode(['success' => true, 'solicitudes' => $solicitudes]);
    exit;
}

// --- POST: CREAR SOLICITUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $nombre_artista = trim($data['nombre_artista'] ?? '');
    $email_contacto = trim($data['email_contacto'] ?? '');
    $descripcion    = trim($data['descripcion'] ?? '');

    if (!$nombre_artista || !$email_contacto || !$descripcion) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        exit;
    }

    $sql = "INSERT INTO solicitudes_artistas (nombre_artista, email_contacto, descripcion) VALUES (?, ?, ?)";
    $stmt = $mysqli->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sss", $nombre_artista, $email_contacto, $descripcion);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Solicitud enviada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar la solicitud']);
        }
        $stmt->close();
    }
    exit;
}

// --- PUT: CAMBIAR ESTADO / RECHAZAR (BORRAR) ---
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? 0);
    $estado = $data['estado'] ?? '';

    if ($id) {
        if ($estado === 'rechazada') {
            // Si el admin la rechaza, la ELIMINAMOS de la base de datos
            $sql = "DELETE FROM solicitudes_artistas WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) echo json_encode(['success' => true, 'accion' => 'borrada']);
            
        } else if ($estado === 'aceptada') {
            // Si la acepta, actualizamos el estado
            $sql = "UPDATE solicitudes_artistas SET estado = ? WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("si", $estado, $id);
            if ($stmt->execute()) echo json_encode(['success' => true, 'accion' => 'aceptada']);
        }
    }
    exit;
}

cerrarConexion($mysqli);
?>