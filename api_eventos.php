<?php
session_start();

// Activar que MySQLi lance excepciones automáticamente para que el try/catch funcione de verdad
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// --- 1. CORS UNIVERSAL ---
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
header("Content-Type: application/json; charset=UTF-8");

require_once "conexion.php";
$mysqli = conexionBBDD();

// --- GET: OBTENER EVENTOS CON SUS TAREAS DINÁMICAS ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id_usuario_actual = $_SESSION['user_id'] ?? 0;
    
    // Aquí 'plazas_reales' solo cuenta registros en 'asistencias' (público general)
    $sql = "SELECT e.*, 
            (SELECT COUNT(*) FROM asistencias WHERE id_evento = e.id) as plazas_reales,
            (SELECT COUNT(*) FROM asistencias WHERE id_evento = e.id AND id_usuario = ?) as estoy_apuntado
            FROM eventos e 
            WHERE e.visible_publico = 1 
            ORDER BY e.fecha_evento ASC";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id_usuario_actual);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $eventos = [];
    while ($evento = $resultado->fetch_assoc()) {
        $evento['estoy_apuntado'] = $evento['estoy_apuntado'] > 0;
        
        // Las plazas libres del evento son independientes de los trabajadores
        $evento['plazas_libres'] = $evento['aforo_max'] - $evento['plazas_reales'];
        
        // --- BUSCAR TAREAS DE ESTE EVENTO ESPECÍFICO (TRABAJADORES) ---
        $id_ev = $evento['id'];
        $sql_tareas = "SELECT t.*, 
                       (SELECT COUNT(*) FROM turnos WHERE id_tarea = t.id) as ocupacion_actual,
                       (SELECT COUNT(*) FROM turnos WHERE id_tarea = t.id AND id_usuario = ?) as estoy_en_esta_tarea
                       FROM evento_tareas t WHERE t.id_evento = ?";
        $stmt_t = $mysqli->prepare($sql_tareas);
        $stmt_t->bind_param("ii", $id_usuario_actual, $id_ev);
        $stmt_t->execute();
        $res_tareas = $stmt_t->get_result();
        
        $evento['tareas'] = [];
        $total_txandalaris_max = 0;
        $total_txandalaris_apuntados = 0;
        
        while($tarea = $res_tareas->fetch_assoc()) {
            $tarea['estoy_en_esta_tarea'] = $tarea['estoy_en_esta_tarea'] > 0;
            $total_txandalaris_max += $tarea['limite_usuarios'];
            $total_txandalaris_apuntados += $tarea['ocupacion_actual'];
            $evento['tareas'][] = $tarea;
        }
        
        $evento['txandalaris_max'] = $total_txandalaris_max;
        $evento['txandalaris_apuntados'] = $total_txandalaris_apuntados;
        
        $eventos[] = $evento;
    }
    echo json_encode(['success' => true, 'eventos' => $eventos]);
    exit;
}

// --- POST / PUT: CREAR Y EDITAR EVENTOS + TAREAS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $titulo = trim($data['titulo'] ?? '');
    $fecha = $data['fecha_evento'] ?? '';
    $hora = $data['hora_inicio'] ?? '';
    $aforo = intval($data['aforo_max'] ?? 120);
    $estado = $data['estado'] ?? 'pendiente';
    $tareas = $data['tareas'] ?? []; 

    $mysqli->begin_transaction();
    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sql = "INSERT INTO eventos (titulo, fecha_evento, hora_inicio, aforo_max, estado, visible_publico) VALUES (?, ?, ?, ?, ?, 1)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sssis", $titulo, $fecha, $hora, $aforo, $estado);
            $stmt->execute();
            $id_evento = $mysqli->insert_id;
        } else {
            $id_evento = intval($data['id']);
            $sql = "UPDATE eventos SET titulo=?, fecha_evento=?, hora_inicio=?, aforo_max=?, estado=? WHERE id=?";
            $stmt = $mysqli->prepare($sql);
            // CORREGIDO: El orden de tipos ahora es "sssisi" (String, String, String, Integer, String, Integer)
            $stmt->bind_param("sssisi", $titulo, $fecha, $hora, $aforo, $estado, $id_evento);
            $stmt->execute();
            
            // Si editamos, limpiamos las tareas viejas usando consultas preparadas por seguridad
            $stmt_del = $mysqli->prepare("DELETE FROM evento_tareas WHERE id_evento = ?");
            $stmt_del->bind_param("i", $id_evento);
            $stmt_del->execute();
        }

        // Insertamos las tareas una a una
        foreach ($tareas as $tar) {
            $nombre_t = trim($tar['nombre_tarea']);
            $limite_u = intval($tar['limite_usuarios'] ?? 1);
            if (!empty($nombre_t)) {
                $stmt_i = $mysqli->prepare("INSERT INTO evento_tareas (id_evento, nombre_tarea, limite_usuarios) VALUES (?, ?, ?)");
                $stmt_i->bind_param("isi", $id_evento, $nombre_t, $limite_u);
                $stmt_i->execute();
            }
        }

        $mysqli->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// --- DELETE: BORRAR ---
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $_GET['id'] ?? $data['id'] ?? null;
    if ($id) {
        $id = intval($id);
        $stmt = $mysqli->prepare("DELETE FROM eventos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Falta ID']);
    }
    exit;
}

cerrarConexion($mysqli);
?>