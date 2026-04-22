<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
header("Content-Type: application/json; charset=UTF-8");

// --- SEGURIDAD OBLIGATORIA ---
if (!isset($_SESSION['rol']) || ($_SESSION['rol'] !== 'txandalari' && $_SESSION['rol'] !== 'admin')) {
    echo json_encode(['estado' => 'error', 'mensaje' => 'Acceso denegado']);
    exit;
}

require_once "conexion.php";
$mysqli = conexionBBDD();

$data = json_decode(file_get_contents('php://input'), true);
$token = trim($data['qr_token'] ?? '');

if (empty($token)) {
    echo json_encode(['estado' => 'error', 'mensaje' => 'Token vacío']);
    exit;
}

// LÓGICA OBLIGATORIA EN ORDEN EXACTO:

// 1 y 2. Leer token y Buscar usuario
$sql_user = "SELECT id, nombre FROM usuarios WHERE qr_token = ?";
$stmt_user = $mysqli->prepare($sql_user);
$stmt_user->bind_param("s", $token);
$stmt_user->execute();
$res_user = $stmt_user->get_result();

if ($res_user->num_rows === 0) {
    echo json_encode(['estado' => 'error', 'mensaje' => 'Token inválido o usuario no existe']);
    exit;
}
$usuario = $res_user->fetch_assoc();
$id_usuario = $usuario['id'];
$nombre_usuario = $usuario['nombre'];


// 3. Buscar evento activo (HOY y Confirmado)
$sql_evento = "SELECT id, aforo_max FROM eventos WHERE fecha_evento = CURDATE() AND estado = 'confirmado' LIMIT 1";
$res_evento = $mysqli->query($sql_evento);

if ($res_evento->num_rows === 0) {
    echo json_encode(['estado' => 'Sin evento', 'mensaje' => 'No hay eventos confirmados para hoy']);
    exit;
}
$evento = $res_evento->fetch_assoc();
$id_evento = $evento['id'];
$aforo_max = $evento['aforo_max'];


// 4. Comprobar duplicado (¿Ya entró hoy a este evento?)
$sql_duplicado = "SELECT id FROM asistencias WHERE id_evento = ? AND id_usuario = ?";
$stmt_dup = $mysqli->prepare($sql_duplicado);
$stmt_dup->bind_param("ii", $id_evento, $id_usuario);
$stmt_dup->execute();
if ($stmt_dup->get_result()->num_rows > 0) {
    echo json_encode(['estado' => 'Ya entró', 'nombre' => $nombre_usuario]);
    exit;
}


// 5 y 6. Contar asistentes y Comparar aforo
$sql_aforo = "SELECT COUNT(*) as total FROM asistencias WHERE id_evento = ?";
$stmt_aforo = $mysqli->prepare($sql_aforo);
$stmt_aforo->bind_param("i", $id_evento);
$stmt_aforo->execute();
$total_asistentes = $stmt_aforo->get_result()->fetch_assoc()['total'];

if ($total_asistentes >= $aforo_max) {
    echo json_encode(['estado' => 'Aforo completo', 'mensaje' => 'Aforo máximo alcanzado']);
    exit;
}


// 7. Insertar asistencia
$sql_insert = "INSERT INTO asistencias (id_evento, id_usuario) VALUES (?, ?)";
$stmt_insert = $mysqli->prepare($sql_insert);
$stmt_insert->bind_param("ii", $id_evento, $id_usuario);

if ($stmt_insert->execute()) {
    // 8. Devolver respuesta OK
    echo json_encode([
        'estado' => 'OK', 
        'nombre' => $nombre_usuario, 
        'ocupacion' => ($total_asistentes + 1) . '/' . $aforo_max
    ]);
} else {
    echo json_encode(['estado' => 'error', 'mensaje' => 'Fallo al registrar entrada']);
}

cerrarConexion($mysqli);
?>