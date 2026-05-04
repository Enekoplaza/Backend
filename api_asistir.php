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
    
    // 🔒 SEGURIDAD OBLIGATORIA: Comprobar la fecha del evento antes de hacer nada
    $sql_fecha = "SELECT fecha_evento FROM eventos WHERE id = ?";
    $stmt_fecha = $mysqli->prepare($sql_fecha);
    $stmt_fecha->bind_param("i", $id_evento);
    $stmt_fecha->execute();
    $res_fecha = $stmt_fecha->get_result();
    
    if ($row = $res_fecha->fetch_assoc()) {
        $hoy = date('Y-m-d');
        if ($row['fecha_evento'] < $hoy) {
            echo json_encode(['success' => false, 'message' => 'Este evento ya ha finalizado. No se permiten cambios de última hora.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'El evento no existe']);
        exit;
    }
    // -------------------------------------------------------------------------

    // ACCIÓN 1: APUNTARSE
    if ($tarea !== null) {
        $sql_asist = "INSERT IGNORE INTO asistencias (id_evento, id_usuario) VALUES (?, ?)";
        $stmt_asist = $mysqli->prepare($sql_asist);
        $stmt_asist->bind_param("ii", $id_evento, $user_id);
        $stmt_asist->execute();

        if (in_array($tarea, ['puerta', 'barra', 'limpieza', 'otros'])) {
            $sql_turno = "INSERT INTO turnos (id_evento, id_usuario, puesto) VALUES (?, ?, ?)
                          ON DUPLICATE KEY UPDATE puesto = ?";
            $stmt_turno = $mysqli->prepare($sql_turno);
            $stmt_turno->bind_param("iiss", $id_evento, $user_id, $tarea, $tarea);
            $stmt_turno->execute();
        } else {
            $sql_del_turno = "DELETE FROM turnos WHERE id_evento = ? AND id_usuario = ?";
            $stmt_del = $mysqli->prepare($sql_del_turno);
            $stmt_del->bind_param("ii", $id_evento, $user_id);
            $stmt_del->execute();
        }

        echo json_encode(['success' => true, 'message' => 'Apuntado correctamente']);
    } 
    // ACCIÓN 2: CANCELAR
    else {
        $stmt_del_asist = $mysqli->prepare("DELETE FROM asistencias WHERE id_evento = ? AND id_usuario = ?");
        $stmt_del_asist->bind_param("ii", $id_evento, $user_id);
        $stmt_del_asist->execute();

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