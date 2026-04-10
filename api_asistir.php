<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once "conexion.php";
$mysqli = conexionBBDD();

$data = json_decode(file_get_contents('php://input'), true);
$id_evento = $data['id_evento'] ?? null;

// Cogemos el usuario directamente de la sesión por seguridad
$id_usuario = $_SESSION['user_id'] ?? null;

if (!$id_evento || !$id_usuario) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para apuntarte']);
    exit;
}

// 1. Comprobamos si ya está apuntado
$sql_check = "SELECT id FROM asistencias WHERE id_evento = ? AND id_usuario = ?";
$stmt_check = $mysqli->prepare($sql_check);
$stmt_check->bind_param("ii", $id_evento, $id_usuario);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // 1. Borramos de la tabla asistencias
    $sql_del = "DELETE FROM asistencias WHERE id_evento = ? AND id_usuario = ?";
    $stmt_del = $mysqli->prepare($sql_del);
    $stmt_del->bind_param("ii", $id_evento, $id_usuario);
    $stmt_del->execute();
    
    // 2. NUEVO: Borramos también de la tabla turnos (por si era un trabajador)
    $sql_del_turno = "DELETE FROM turnos WHERE id_evento = ? AND id_usuario = ?";
    $stmt_del_turno = $mysqli->prepare($sql_del_turno);
    $stmt_del_turno->bind_param("ii", $id_evento, $id_usuario);
    $stmt_del_turno->execute();
    
    echo json_encode(['success' => true, 'accion' => 'desapuntado', 'message' => 'Te has borrado del evento']);
} else {
    // Si NO está apuntado -> Comprobamos aforo y LO APUNTAMOS
    $sql_aforo = "SELECT aforo_max, (SELECT COUNT(*) FROM asistencias WHERE id_evento = ?) as ocupadas FROM eventos WHERE id = ?";
    $stmt_aforo = $mysqli->prepare($sql_aforo);
    $stmt_aforo->bind_param("ii", $id_evento, $id_evento);
    $stmt_aforo->execute();
    $res_aforo = $stmt_aforo->get_result()->fetch_assoc();
    
    if ($res_aforo['ocupadas'] >= $res_aforo['aforo_max']) {
         echo json_encode(['success' => false, 'message' => 'Lo sentimos, el aforo está completo']);
         exit;
    }

    $sql_ins = "INSERT INTO asistencias (id_evento, id_usuario) VALUES (?, ?)";
    $stmt_ins = $mysqli->prepare($sql_ins);
    $stmt_ins->bind_param("ii", $id_evento, $id_usuario);
    $stmt_ins->execute();
    
    echo json_encode(['success' => true, 'accion' => 'apuntado', 'message' => '¡Plaza reservada con éxito!']);
}

cerrarConexion($mysqli);
?>