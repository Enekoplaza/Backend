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

// --- MAGIA: Borrado automático de eventos de hace más de 1 semana ---
$mysqli->query("DELETE FROM eventos WHERE fecha_evento < DATE_SUB(CURDATE(), INTERVAL 7 DAY)");

// --- GET: OBTENER EVENTOS ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id_usuario_actual = $_SESSION['user_id'] ?? 0;
    $sql = "SELECT e.*, 
            (SELECT COUNT(*) FROM asistencias WHERE id_evento = e.id) as plazas_ocupadas,
            (SELECT COUNT(*) FROM asistencias WHERE id_evento = e.id AND id_usuario = ?) as estoy_apuntado
            FROM eventos e ORDER BY fecha_evento ASC";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id_usuario_actual);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $eventos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $fila['estoy_apuntado'] = $fila['estoy_apuntado'] > 0;
        $fila['plazas_libres'] = $fila['aforo_max'] - $fila['plazas_ocupadas'];
        $eventos[] = $fila;
    }
    echo json_encode(['success' => true, 'eventos' => $eventos]);
    exit;
}

// --- POST: CREAR EVENTO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $titulo       = trim($data['titulo'] ?? '');
    $fecha_evento = $data['fecha_evento'] ?? '';
    $hora_inicio  = $data['hora_inicio'] ?? '';
    $aforo_max    = intval($data['aforo_max'] ?? 120);
    $estado       = $data['estado'] ?? 'pendiente';

    if (!$titulo || !$fecha_evento || !$hora_inicio) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']);
        exit;
    }

    // 🔒 SEGURIDAD: Bloquear fechas pasadas
    $hoy = date('Y-m-d');
    if ($fecha_evento < $hoy) {
        echo json_encode(['success' => false, 'message' => 'No puedes crear eventos con una fecha anterior a hoy.']);
        exit;
    }

    $sql = "INSERT INTO eventos (titulo, fecha_evento, hora_inicio, aforo_max, estado, visible_publico) VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssis", $titulo, $fecha_evento, $hora_inicio, $aforo_max, $estado);
    if ($stmt->execute()) echo json_encode(['success' => true, 'message' => 'Evento creado con éxito']);
    exit;
}

// --- PUT: EDITAR EVENTO ---
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id           = $data['id'] ?? null;
    $titulo       = trim($data['titulo'] ?? '');
    $fecha_evento = $data['fecha_evento'] ?? '';
    $hora_inicio  = $data['hora_inicio'] ?? '';
    $aforo_max    = intval($data['aforo_max'] ?? 120);
    $estado       = $data['estado'] ?? 'pendiente';

    if ($id) {
        // LÓGICA DE CANCELACIÓN: Si pasa a cancelado, vaciamos listas
        if ($estado === 'cancelado') {
            $stmt_del1 = $mysqli->prepare("DELETE FROM asistencias WHERE id_evento = ?");
            $stmt_del1->bind_param("i", $id);
            $stmt_del1->execute();
            
            $stmt_del2 = $mysqli->prepare("DELETE FROM turnos WHERE id_evento = ?");
            $stmt_del2->bind_param("i", $id);
            $stmt_del2->execute();
        }

        $sql = "UPDATE eventos SET titulo=?, fecha_evento=?, hora_inicio=?, aforo_max=?, estado=? WHERE id=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssisi", $titulo, $fecha_evento, $hora_inicio, $aforo_max, $estado, $id);
        if ($stmt->execute()) echo json_encode(['success' => true, 'message' => 'Evento actualizado correctamente']);
    }
    exit;
}

// --- DELETE: BORRAR EVENTO ---
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    if ($id) {
        $sql = "DELETE FROM eventos WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Evento borrado correctamente']);
    }
    exit;
}

cerrarConexion($mysqli);
?>