<?php
session_start();

// --- CORS UNIVERSAL (Funciona en local y en servidor) ---
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
// -----------------------------------------------------------------
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['logged_in' => false]);
    exit;
}

require_once "conexion.php";
$mysqli = conexionBBDD();

// ========================================================================
// 🧹 CUMPLIMIENTO RGPD (SPRINT 5): LIMPIEZA AUTOMÁTICA DE ASISTENCIAS
// Borra los registros de acceso que tengan más de 30 días de antigüedad
// ========================================================================
$mysqli->query("DELETE FROM asistencias WHERE fecha_hora_entrada < NOW() - INTERVAL 30 DAY");

$user_id = $_SESSION['user_id'];
// Añadido qr_token a la consulta
$sql = "SELECT id, nombre, dni, email, direccion, rol, solicitud_txandalari, qr_token FROM usuarios WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    $_SESSION['nombre'] = $user['nombre'];
    $_SESSION['rol'] = $user['rol'];
    
    echo json_encode([
        'logged_in' => true,
        'id' => $user['id'],
        'nombre' => $user['nombre'],
        'dni' => $user['dni'],
        'email' => $user['email'],
        'direccion' => $user['direccion'],
        'rol' => $user['rol'],
        'solicitud_txandalari' => $user['solicitud_txandalari'],
        'qr_token' => $user['qr_token']
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}

cerrarConexion($mysqli);
?>