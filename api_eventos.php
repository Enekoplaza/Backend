<?php
session_start();

header("Access-Control-Allow-Origin: http://localhost:5173");
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

// --- GET: OBTENER EVENTOS ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id_usuario_actual = $_SESSION['user_id'] ?? 0;
    
    // Consulta mejorada para contar puestos específicos
    $sql = "SELECT e.*, 
            (SELECT COUNT(*) FROM asistencias WHERE id_evento = e.id) as plazas_ocupadas,
            (SELECT COUNT(*) FROM asistencias WHERE id_evento = e.id AND id_usuario = ?) as estoy_apuntado,
            (SELECT COUNT(*) FROM turnos WHERE id_evento = e.id) as txandalaris_apuntados,
            (SELECT COUNT(*) FROM turnos WHERE id_evento = e.id AND puesto = 'barra') as ocupacion_barra,
            (SELECT COUNT(*) FROM turnos WHERE id_evento = e.id AND puesto = 'puerta') as ocupacion_puerta,
            (SELECT COUNT(*) FROM turnos WHERE id_evento = e.id AND puesto = 'limpieza') as ocupacion_limpieza,
            (SELECT COUNT(*) FROM turnos WHERE id_evento = e.id AND puesto = 'otros') as ocupacion_otros
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

// --- POST: CREAR EVENTO (Con txandalaris_max por defecto) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $titulo          = trim($data['titulo'] ?? '');
    $fecha_evento    = $data['fecha_evento'] ?? '';
    $hora_inicio     = $data['hora_inicio'] ?? '';
    $aforo_max       = intval($data['aforo_max'] ?? 120);
    $txandalaris_max = intval($data['txandalaris_max'] ?? 6); // Valor por defecto
    $estado          = $data['estado'] ?? 'pendiente';

    $sql = "INSERT INTO eventos (titulo, fecha_evento, hora_inicio, aforo_max, txandalaris_max, estado, visible_publico) VALUES (?, ?, ?, ?, ?, ?, 1)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssiis", $titulo, $fecha_evento, $hora_inicio, $aforo_max, $txandalaris_max, $estado);
    if ($stmt->execute()) echo json_encode(['success' => true]);
    exit;
}
// ... (El resto de PUT y DELETE siguen igual pero añadiendo txandalaris_max en el UPDATE)
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