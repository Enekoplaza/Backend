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
if (!$user_id) { 
    echo json_encode(['success' => false, 'message' => 'No autorizado']); 
    exit; 
}

$data = json_decode(file_get_contents('php://input'), true);
$id_evento = intval($data['id_evento'] ?? 0);
$id_tarea = isset($data['id_tarea']) ? intval($data['id_tarea']) : null;

if ($id_evento) {
    
    // ==========================================
    // ACCIÓN 1: APUNTARSE A TRABAJAR (TXANDALARI)
    // ==========================================
    if ($id_tarea !== null && $id_tarea > 0) {
        
        $mysqli->begin_transaction();
        try {
            // Comprobar el cupo de la tarea de trabajo
            $sql_cupo = "SELECT t.limite_usuarios, 
                        (SELECT COUNT(*) FROM turnos WHERE id_tarea = t.id) as ocupados 
                        FROM evento_tareas t WHERE t.id = ?";
            $stmt_check = $mysqli->prepare($sql_cupo);
            $stmt_check->bind_param("i", $id_tarea);
            $stmt_check->execute();
            $res_cupo = $stmt_check->get_result()->fetch_assoc();

            if (!$res_cupo) {
                throw new Exception('Ez da txanda aurkitu / No se encontró la tarea.');
            }

            if ($res_cupo['ocupados'] >= $res_cupo['limite_usuarios']) {
                throw new Exception('Barkatu, txanda hau beteta dago (Turno lleno).');
            }

            // 🌟 CLAVE: Aquí ya NO tocamos la tabla 'asistencias'. Solo guardamos el turno de trabajo.
            $sql_turno = "INSERT INTO turnos (id_evento, id_usuario, id_tarea) VALUES (?, ?, ?) 
                          ON DUPLICATE KEY UPDATE id_tarea = ?";
            $stmt_turno = $mysqli->prepare($sql_turno);
            $stmt_turno->bind_param("iiii", $id_evento, $user_id, $id_tarea, $id_tarea);
            $stmt_turno->execute();

            $mysqli->commit();
            echo json_encode(['success' => true, 'message' => 'Apuntado al turno correctamente']);
            
        } catch (Exception $e) {
            $mysqli->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } 
    
    // ==========================================
    // ACCIÓN 2: CANCELAR TURNO DE TRABAJO
    // ==========================================
    else {
        // 🌟 CLAVE: Al cancelar el turno, solo te borramos de la tabla de trabajo 'turnos'
        $stmt_del_turno = $mysqli->prepare("DELETE FROM turnos WHERE id_evento = ? AND id_usuario = ?");
        $stmt_del_turno->bind_param("ii", $id_evento, $user_id);
        $stmt_del_turno->execute();

        echo json_encode(['success' => true, 'message' => 'Turno cancelado correctamente']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Falta el ID del evento']);
}

cerrarConexion($mysqli);
?>