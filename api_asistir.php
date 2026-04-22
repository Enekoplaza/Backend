<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
header("Content-Type: application/json; charset=UTF-8");

require_once "conexion.php";
$mysqli = conexionBBDD();

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id_evento = intval($data['id_evento'] ?? 0);
$tarea = $data['tarea'] ?? null;

if ($id_evento) {
    // -------------------------------------------------------------
    // ACCIÓN 1: APUNTARSE (Viene de Eventos.vue porque trae "tarea")
    // -------------------------------------------------------------
    if ($tarea !== null) {
        
        // 1. Lo metemos en ASISTENCIAS para restar la plaza (IGNORE evita errores si ya estaba)
        $sql_asist = "INSERT IGNORE INTO asistencias (id_evento, id_usuario) VALUES (?, ?)";
        $stmt_asist = $mysqli->prepare($sql_asist);
        $stmt_asist->bind_param("ii", $id_evento, $user_id);
        $stmt_asist->execute();

        // 2. Le asignamos la TAREA en TURNOS
        if (in_array($tarea, ['puerta', 'barra', 'limpieza', 'otros'])) {
            // Si ya tenía turno, lo actualiza (ON DUPLICATE KEY UPDATE)
            $sql_turno = "INSERT INTO turnos (id_evento, id_usuario, puesto) VALUES (?, ?, ?)
                          ON DUPLICATE KEY UPDATE puesto = ?";
            $stmt_turno = $mysqli->prepare($sql_turno);
            $stmt_turno->bind_param("iiss", $id_evento, $user_id, $tarea, $tarea);
            $stmt_turno->execute();
        } else {
            // Si elige "Posturik ez", le borramos de turnos por si antes estaba en la puerta
            $sql_del_turno = "DELETE FROM turnos WHERE id_evento = ? AND id_usuario = ?";
            $stmt_del = $mysqli->prepare($sql_del_turno);
            $stmt_del->bind_param("ii", $id_evento, $user_id);
            $stmt_del->execute();
        }

        echo json_encode(['success' => true, 'message' => 'Apuntado correctamente']);
    } 
    // -------------------------------------------------------------
    // ACCIÓN 2: CANCELAR (Viene de Perfil.vue porque NO trae "tarea")
    // -------------------------------------------------------------
    else {
        // Le borramos de ASISTENCIAS (libera aforo)
        $stmt_del_asist = $mysqli->prepare("DELETE FROM asistencias WHERE id_evento = ? AND id_usuario = ?");
        $stmt_del_asist->bind_param("ii", $id_evento, $user_id);
        $stmt_del_asist->execute();

        // Le borramos de TURNOS
        $stmt_del_turno = $mysqli->prepare("DELETE FROM turnos WHERE id_evento = ? AND id_usuario = ?");
        $stmt_del_turno->bind_param("ii", $id_evento, $user_id);
        $stmt_del_turno->execute();

        echo json_encode(['success' => true, 'message' => 'Cancelado correctamente']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Falta el ID del evento']);
}

cerrarConexion($mysqli);
?>