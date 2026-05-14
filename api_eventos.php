<?php
session_start();

// --- 1. CORS UNIVERSAL (Funciona en local y servidor) ---
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

// --- GET: OBTENER EVENTOS ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id_usuario_actual = $_SESSION['user_id'] ?? 0;
    
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
        $fila['plazas_libres'] = $fila['aforo_max'] - $fila['plazas_reales'];
        $eventos[] = $fila;
    }
    echo json_encode(['success' => true, 'eventos' => $eventos]);
    exit;
}

// --- POST y PUT (CREAR Y EDITAR) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $titulo = trim($data['titulo'] ?? '');
    $fecha = $data['fecha_evento'] ?? '';
    $hora = $data['hora_inicio'] ?? '';
    $aforo = intval($data['aforo_max'] ?? 120);
    $t_max = intval($data['txandalaris_max'] ?? 8); // Cambiado a 8 por defecto
    $estado = $data['estado'] ?? 'pendiente';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $sql = "INSERT INTO eventos (titulo, fecha_evento, hora_inicio, aforo_max, txandalaris_max, estado, visible_publico) VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssiis", $titulo, $fecha, $hora, $aforo, $t_max, $estado);
    } else {
        $id = $data['id'] ?? null;
        $sql = "UPDATE eventos SET titulo=?, fecha_evento=?, hora_inicio=?, aforo_max=?, txandalaris_max=?, estado=? WHERE id=?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssiisi", $titulo, $fecha, $hora, $aforo, $t_max, $estado, $id);
    }
    
    if ($stmt->execute()) echo json_encode(['success' => true]);
    exit;
}

// --- DELETE: BORRAR ---
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Restaurado el método seguro para leer el ID desde la URL
    $id = $_GET['id'] ?? null;
    if ($id) {
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