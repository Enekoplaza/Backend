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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id_usuario_actual = $_SESSION['user_id'] ?? 0;
    
    // CONSULTA OPTIMIZADA:
    // plazas_ocupadas: Cuenta personas en 'asistencias' que NO están en la tabla 'turnos' 
    // (es decir, solo cuenta a los socios que van a disfrutar, no a los que van a trabajar).
    
    $sql = "SELECT e.*, 
            (SELECT COUNT(*) FROM asistencias a 
             WHERE a.id_evento = e.id 
             AND a.id_usuario NOT IN (SELECT t.id_usuario FROM turnos t WHERE t.id_evento = e.id)
            ) as plazas_reales,
            (SELECT COUNT(*) FROM asistencias WHERE id_evento = e.id AND id_usuario = ?) as estoy_apuntado,
            (SELECT COUNT(*) FROM turnos WHERE id_evento = e.id) as txandalaris_apuntados,
            (SELECT COUNT(*) FROM turnos WHERE id_evento = e.id AND puesto = 'barra') as ocupacion_barra,
            (SELECT COUNT(*) FROM turnos WHERE id_evento = e.id AND puesto = 'puerta') as ocupacion_puerta,
            (SELECT COUNT(*) FROM turnos WHERE id_evento = e.id AND puesto = 'limpieza') as ocupacion_limpieza,
            (SELECT COUNT(*) FROM turnos WHERE id_evento = e.id AND puesto = 'otros') as ocupacion_otros
            FROM eventos e 
            WHERE e.visible_publico = 1
            ORDER BY e.fecha_evento ASC";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id_usuario_actual);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $eventos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $fila['estoy_apuntado'] = $fila['estoy_apuntado'] > 0;
        
        // El aforo máximo menos los socios (los txandalaris no restan aquí)
        $fila['plazas_libres'] = $fila['aforo_max'] - $fila['plazas_reales'];
        
        $eventos[] = $fila;
    }
    echo json_encode(['success' => true, 'eventos' => $eventos]);
    exit;
}

// --- POST: CREAR EVENTO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $titulo = trim($data['titulo'] ?? '');
    $fecha = $data['fecha_evento'] ?? '';
    $hora = $data['hora_inicio'] ?? '';
    $aforo = intval($data['aforo_max'] ?? 120);
    $t_max = intval($data['txandalaris_max'] ?? 6);
    $estado = $data['estado'] ?? 'pendiente';

    $sql = "INSERT INTO eventos (titulo, fecha_evento, hora_inicio, aforo_max, txandalaris_max, estado, visible_publico) VALUES (?, ?, ?, ?, ?, ?, 1)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssiis", $titulo, $fecha, $hora, $aforo, $t_max, $estado);
    if ($stmt->execute()) echo json_encode(['success' => true]);
    exit;
}

// --- PUT: EDITAR EVENTO ---
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    if ($id) {
        $sql = "UPDATE eventos SET titulo=?, fecha_evento=?, hora_inicio=?, aforo_max=?, txandalaris_max=?, estado=? WHERE id=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssiisi", $data['titulo'], $data['fecha_evento'], $data['hora_inicio'], $data['aforo_max'], $data['txandalaris_max'], $data['estado'], $id);
        if ($stmt->execute()) echo json_encode(['success' => true]);
    }
    exit;
}

// --- DELETE: BORRAR ---
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    if ($id) {
        $mysqli->query("DELETE FROM eventos WHERE id = $id");
        echo json_encode(['success' => true]);
    }
    exit;
}

cerrarConexion($mysqli);
?>