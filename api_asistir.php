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
$tarea = $data['tarea'] ?? null; // Viene del popup de SweetAlert
$id_usuario = $_SESSION['user_id'] ?? null;

if (!$id_evento || !$id_usuario) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit;
}

// Comprobar si ya existe
$check = $mysqli->prepare("SELECT id FROM asistencias WHERE id_evento = ? AND id_usuario = ?");
$check->bind_param("ii", $id_evento, $id_usuario);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    // DESAPUNTAR
    $mysqli->prepare("DELETE FROM asistencias WHERE id_evento = ? AND id_usuario = ?")->execute([$id_evento, $id_usuario]);
    $mysqli->prepare("DELETE FROM turnos WHERE id_evento = ? AND id_usuario = ?")->execute([$id_evento, $id_usuario]);
    echo json_encode(['success' => true, 'message' => 'Borrado']);
} else {
    // APUNTAR
    // 1. Siempre a asistencias
    $ins1 = $mysqli->prepare("INSERT INTO asistencias (id_evento, id_usuario) VALUES (?, ?)");
    $ins1->bind_param("ii", $id_evento, $id_usuario);
    $ins1->execute();

    // 2. Si hay tarea, a TURNOS (columna 'puesto')
    if (!empty($tarea)) {
        $ins2 = $mysqli->prepare("INSERT INTO turnos (id_evento, id_usuario, puesto) VALUES (?, ?, ?)");
        $ins2->bind_param("iis", $id_evento, $id_usuario, $tarea);
        
        if (!$ins2->execute()) {
            // Esto guardará el error en el log de PHP si la tabla o columna fallan
            error_log("Error en tabla turnos: " . $mysqli->error);
        }
    }
    echo json_encode(['success' => true, 'message' => 'Guardado correctamente']);
}
cerrarConexion($mysqli);
?>