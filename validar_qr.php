<?php

session_start();

$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}
header("Content-Type: application/json; charset=UTF-8");

// Permitimos si es admin/txandalari O si tiene la bandera de sesión del enlace temporal
$acceso_invitado = $_SESSION['acceso_puerta_temporal'] ?? false;

if (!$acceso_invitado && (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'txandalari' && $_SESSION['rol'] !== 'admin'))) {
    echo json_encode(['estado' => 'error', 'mensaje' => 'Acceso denegado']);
    exit;
}

// Conexión correcta usando TU método mysqli
require_once "conexion.php";
$mysqli = conexionBBDD();

// --- 1. Leer token ---
$data = json_decode(file_get_contents('php://input'), true);
$token = trim($data['token'] ?? '');

if (empty($token)) {
    echo json_encode(['estado' => 'error', 'mensaje' => 'Token QR vacío']);
    exit;
}

// --- 2. Buscar usuario (Por qr_token) ---
$sql_user = "SELECT id, nombre FROM usuarios WHERE qr_token = ?";
$stmt_user = $mysqli->prepare($sql_user);
$stmt_user->bind_param("s", $token);
$stmt_user->execute();
$res_user = $stmt_user->get_result();

if ($res_user->num_rows === 0) {
    echo json_encode(['estado' => 'error', 'mensaje' => 'Código QR no reconocido']);
    exit;
}

$usuario = $res_user->fetch_assoc();
$id_usuario = $usuario['id'];
$nombre_usuario = $usuario['nombre'];

// --- 3. Buscar evento activo ---
$sql_evento = "SELECT id, aforo_max FROM eventos WHERE fecha_evento = CURDATE() AND estado = 'confirmado' LIMIT 1";
$res_evento = $mysqli->query($sql_evento);

if ($res_evento->num_rows === 0) {
    echo json_encode(['estado' => 'Sin evento', 'mensaje' => 'No hay eventos hoy']);
    exit;
}

$evento = $res_evento->fetch_assoc();
$id_evento = $evento['id'];
$aforo_max = $evento['aforo_max'];

// --- 4. Comprobar duplicado ---
$sql_dup = "SELECT id FROM asistencias WHERE id_evento = ? AND id_usuario = ?";
$stmt_dup = $mysqli->prepare($sql_dup);
$stmt_dup->bind_param("ii", $id_evento, $id_usuario);
$stmt_dup->execute();

if ($stmt_dup->get_result()->num_rows > 0) {
    echo json_encode(['estado' => 'Ya entró', 'nombre' => $nombre_usuario]);
    exit;
}

// --- 5. Contar asistentes ---
$sql_count = "SELECT COUNT(*) as total FROM asistencias WHERE id_evento = ?";
$stmt_count = $mysqli->prepare($sql_count);
$stmt_count->bind_param("i", $id_evento);
$stmt_count->execute();
$total_asistentes = $stmt_count->get_result()->fetch_assoc()['total'];

// --- 6. Comparar aforo ---
if ($total_asistentes >= $aforo_max) {
    echo json_encode(['estado' => 'Aforo completo', 'mensaje' => 'Aforo máximo alcanzado']);
    exit;
}

// --- 7. Insertar asistencia ---
$sql_insert = "INSERT INTO asistencias (id_evento, id_usuario) VALUES (?, ?)";
$stmt_insert = $mysqli->prepare($sql_insert);
$stmt_insert->bind_param("ii", $id_evento, $id_usuario);

if ($stmt_insert->execute()) {
    // --- 8. Devolver respuesta ---
    $restantes = $aforo_max - ($total_asistentes + 1);
    echo json_encode([
        'estado' => 'OK',
        'nombre' => $nombre_usuario,
        'ocupacion' => "Restantes: $restantes / $aforo_max"
    ]);
} else {
    echo json_encode(['estado' => 'error', 'mensaje' => 'Error al registrar entrada']);
}

cerrarConexion($mysqli);
?>