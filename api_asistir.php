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
$id_usuario = $_SESSION['user_id'] ?? null;

if (!$id_evento || !$id_usuario) {
    echo json_encode(['success'=>false,'message'=>'Debes iniciar sesión']);
    exit;
}

// 1. Comprobar si ya está apuntado
$stmt_check = $mysqli->prepare("SELECT id FROM asistencias WHERE id_evento=? AND id_usuario=?");
$stmt_check->bind_param("ii",$id_evento,$id_usuario);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // Desapuntar -> eliminar de asistencias y sumar 1 al aforo
    $stmt_del = $mysqli->prepare("DELETE FROM asistencias WHERE id_evento=? AND id_usuario=?");
    $stmt_del->bind_param("ii",$id_evento,$id_usuario);
    $stmt_del->execute();

    // SUMAR 1 a aforo_max en la base de datos
    $stmt_aforo = $mysqli->prepare("UPDATE eventos SET aforo_max = aforo_max + 1 WHERE id = ?");
    $stmt_aforo->bind_param("i",$id_evento);
    $stmt_aforo->execute();

    echo json_encode(['success'=>true,'accion'=>'desapuntado','message'=>'Te has borrado del evento']);
} else {
    // Apuntar -> comprobar aforo
    $stmt_aforo = $mysqli->prepare("SELECT aforo_max FROM eventos WHERE id=?");
    $stmt_aforo->bind_param("i",$id_evento);
    $stmt_aforo->execute();
    $res_aforo = $stmt_aforo->get_result()->fetch_assoc();

    if ($res_aforo['aforo_max'] <= 0) {
        echo json_encode(['success'=>false,'message'=>'El aforo está completo']);
        exit;
    }

    // Insertar asistencia
    $stmt_ins = $mysqli->prepare("INSERT INTO asistencias (id_evento,id_usuario) VALUES (?,?)");
    $stmt_ins->bind_param("ii",$id_evento,$id_usuario);
    $stmt_ins->execute();

    // RESTAR 1 a aforo_max en la base de datos
    $stmt_update = $mysqli->prepare("UPDATE eventos SET aforo_max = aforo_max - 1 WHERE id = ?");
    $stmt_update->bind_param("i",$id_evento);
    $stmt_update->execute();

    echo json_encode(['success'=>true,'accion'=>'apuntado','message'=>'¡Plaza reservada!']);
}

cerrarConexion($mysqli);
?>