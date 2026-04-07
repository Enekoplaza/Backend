<?php

//--------Este archivo se encargará de las 3 cosas del perfil:--------------------- 
//--------Obtener tus eventos (GET), actualizar tus datos (PUT) y pedir ser Txandalari (PATCH).---------
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, PUT, PATCH, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once "conexion.php";
$mysqli = conexionBBDD();

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// 1. GET: OBTENER MIS EVENTOS APUNTADOS
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT e.* FROM eventos e 
            INNER JOIN asistencias a ON e.id = a.id_evento 
            WHERE a.id_usuario = ? ORDER BY e.fecha_evento ASC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $eventos = [];
    while ($row = $result->fetch_assoc()) {
        $eventos[] = $row;
    }
    echo json_encode(['success' => true, 'eventos' => $eventos]);
    exit;
}

// 2. PUT: ACTUALIZAR MIS DATOS (Nombre, DNI, Email, Dirección)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $nombre = trim($data['nombre'] ?? '');
    $dni = trim($data['dni'] ?? '');
    $email = trim($data['email'] ?? '');
    $direccion = trim($data['direccion'] ?? '');

    $sql = "UPDATE usuarios SET nombre=?, dni=?, email=?, direccion=? WHERE id=?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ssssi", $nombre, $dni, $email, $direccion, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Perfil actualizado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
    }
    exit;
}

// 3. PATCH: SOLICITAR SER TXANDALARI
if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    // Cambiamos el 0 por el 1 en la tabla de usuarios
    $sql = "UPDATE usuarios SET solicitud_txandalari = 1 WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Solicitud enviada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al solicitar']);
    }
    exit;
}

cerrarConexion($mysqli);
?>