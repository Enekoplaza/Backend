<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once "conexion.php";
$mysqli = conexionBBDD();

// --- SI LA PETICIÓN ES GET: DEVOLVER TODOS LOS EVENTOS ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obtenemos el ID del usuario si está logueado, si no, es 0
    $id_usuario_actual = $_SESSION['user_id'] ?? 0;
    
    // Una consulta SQL avanzada que cuenta las plazas y mira si el usuario está apuntado
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
        $fila['estoy_apuntado'] = $fila['estoy_apuntado'] > 0; // True o False
        $fila['plazas_libres'] = $fila['aforo_max'] - $fila['plazas_ocupadas'];
        $eventos[] = $fila;
    }
    
    echo json_encode(['success' => true, 'eventos' => $eventos]);
    exit;
}

// --- SI LA PETICIÓN ES POST: CREAR UN NUEVO EVENTO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Solo permitimos crear si el usuario logueado es Admin (seguridad extra)
    // *Nota: Comentamos esto por un segundo si quieres probarlo sin hacer login de admin, 
    // pero en producción debe ir descomentado:
    /*
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'No tienes permisos de administrador']);
        exit;
    }
    */

    $titulo       = trim($data['titulo'] ?? '');
    $fecha_evento = $data['fecha_evento'] ?? '';
    $hora_inicio  = $data['hora_inicio'] ?? '';
    $aforo_max    = intval($data['aforo_max'] ?? 120);
    $estado       = $data['estado'] ?? 'pendiente';

    if (!$titulo || !$fecha_evento || !$hora_inicio) {
        echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']);
        exit;
    }

    $sql = "INSERT INTO eventos (titulo, fecha_evento, hora_inicio, aforo_max, estado, visible_publico) VALUES (?, ?, ?, ?, ?, 1)";
    $stmt = $mysqli->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("sssis", $titulo, $fecha_evento, $hora_inicio, $aforo_max, $estado);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Evento creado con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear evento']);
        }
        $stmt->close();
    }
    exit;
}

cerrarConexion($mysqli);
?>