<?php
session_start();

// --- CORS UNIVERSAL ---
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
$tarea = $data['tarea'] ?? null; // Ahora recibimos la palabra: 'barra', 'puerta', etc.

if ($id_evento) {
    
    // 🔒 SEGURIDAD 1: Comprobar la fecha y obtener los límites dinámicos del evento
    $sql_evento = "SELECT fecha_evento, max_puerta, max_barra, max_limpieza, max_otros FROM eventos WHERE id = ?";
    $stmt_evento = $mysqli->prepare($sql_evento);
    $stmt_evento->bind_param("i", $id_evento);
    $stmt_evento->execute();
    $res_evento = $stmt_evento->get_result();
    
    if ($row_evento = $res_evento->fetch_assoc()) {
        $hoy = date('Y-m-d');
        if ($row_evento['fecha_evento'] < $hoy) {
            echo json_encode(['success' => false, 'message' => 'Este evento ya ha finalizado.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'El evento no existe']);
        exit;
    }

    // ==========================================
    // ACCIÓN 1: APUNTARSE A TRABAJAR (TXANDALARI)
    // ==========================================
    if ($tarea !== null) {
        
        // 🔒 SEGURIDAD 2: Comprobar el cupo dinámico de ese puesto específico
        if (in_array($tarea, ['puerta', 'barra', 'limpieza', 'otros'])) {
            
            // Averiguar cuál es el límite para este puesto leyendo la columna correspondiente
            $columna_limite = 'max_' . $tarea; 
            $limite_puesto = intval($row_evento[$columna_limite]);

            // Contar cuántos hay apuntados ya
            $stmt_check = $mysqli->prepare("SELECT COUNT(*) FROM turnos WHERE id_evento = ? AND puesto = ?");
            $stmt_check->bind_param("is", $id_evento, $tarea);
            $stmt_check->execute();
            $ocupados = $stmt_check->get_result()->fetch_row()[0];
            
            if ($ocupados >= $limite_puesto) {
                echo json_encode(['success' => false, 'message' => "El puesto de $tarea ya está completo (Máx $limite_puesto)."]);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Puesto no válido.']);
            exit;
        }

        // Si hay hueco, guardamos la asistencia general...
        $sql_asist = "INSERT IGNORE INTO asistencias (id_evento, id_usuario) VALUES (?, ?)";
        $stmt_asist = $mysqli->prepare($sql_asist);
        $stmt_asist->bind_param("ii", $id_evento, $user_id);
        $stmt_asist->execute();

        // ... y guardamos el turno específico
        $sql_turno = "INSERT INTO turnos (id_evento, id_usuario, puesto) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE puesto = ?";
        $stmt_turno = $mysqli->prepare($sql_turno);
        $stmt_turno->bind_param("iiss", $id_evento, $user_id, $tarea, $tarea);
        $stmt_turno->execute();

        echo json_encode(['success' => true, 'message' => 'Apuntado correctamente']);
    } 
    
    // ==========================================
    // ACCIÓN 2: CANCELAR ASISTENCIA Y TURNO
    // ==========================================
    else {
        // Le borramos de la tabla de asistencias generales
        $stmt_del_asist = $mysqli->prepare("DELETE FROM asistencias WHERE id_evento = ? AND id_usuario = ?");
        $stmt_del_asist->bind_param("ii", $id_evento, $user_id);
        $stmt_del_asist->execute();

        // Le borramos de la tabla de turnos de trabajo
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