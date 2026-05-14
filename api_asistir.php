<?php
session_start();

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
header("Content-Type: application/json; charset=UTF-8");

require_once "conexion.php";
$mysqli = conexionBBDD();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { echo json_encode(['success' => false, 'message' => 'No autorizado']); exit; }

$data = json_decode(file_get_contents('php://input'), true);
$id_evento = intval($data['id_evento'] ?? 0);
$tarea = $data['tarea'] ?? null;

if ($id_evento) {
    // ACCIÓN 1: APUNTARSE
    if ($tarea !== null) {
        
        // 🔒 REGLA: Comprobar que no haya ya 2 personas en ese puesto
        if (in_array($tarea, ['puerta', 'barra', 'limpieza', 'otros'])) {
            $stmt_check = $mysqli->prepare("SELECT COUNT(*) FROM turnos WHERE id_evento = ? AND puesto = ?");
            $stmt_check->bind_param("is", $id_evento, $tarea);
            $stmt_check->execute();
            $count = $stmt_check->get_result()->fetch_row()[0];
            
            if ($count >= 2) {
                echo json_encode(['success' => false, 'message' => "El puesto de $tarea ya está completo (Máx 2)."]);
                exit;
            }
        }

        // Si pasa la comprobación, insertamos
        $sql_asist = "INSERT IGNORE INTO asistencias (id_evento, id_usuario) VALUES (?, ?)";
        $stmt_asist = $mysqli->prepare($sql_asist);
        $stmt_asist->bind_param("ii", $id_evento, $user_id);
        $stmt_asist->execute();

        if (in_array($tarea, ['puerta', 'barra', 'limpieza', 'otros'])) {
            $sql_turno = "INSERT INTO turnos (id_evento, id_usuario, puesto) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE puesto = ?";
            $stmt_turno = $mysqli->prepare($sql_turno);
            $stmt_turno->bind_param("iiss", $id_evento, $user_id, $tarea, $tarea);
            $stmt_turno->execute();
        } else {
            $stmt_del = $mysqli->prepare("DELETE FROM turnos WHERE id_evento = ? AND id_usuario = ?");
            $stmt_del->bind_param("ii", $id_evento, $user_id);
            $stmt_del->execute();
        }

        echo json_encode(['success' => true, 'message' => 'Apuntado correctamente']);
    } 
    // ACCIÓN 2: CANCELAR
    else {
        // ... (resto de la lógica de cancelar)
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