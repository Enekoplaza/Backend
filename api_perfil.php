<?php
session_start();

// --- 1. CORS UNIVERSAL (Funciona en local y servidor) ---
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
// Quitamos el POST de los métodos permitidos porque ya no se usa aquí
header("Access-Control-Allow-Methods: GET, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once "conexion.php";
$mysqli = conexionBBDD();

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// 1. GET: OBTENER MIS EVENTOS APUNTADOS Y MI TURNO
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT e.*, t.puesto 
            FROM eventos e 
            INNER JOIN asistencias a ON e.id = a.id_evento 
            LEFT JOIN turnos t ON e.id = t.id_evento AND t.id_usuario = ?
            WHERE a.id_usuario = ? ORDER BY e.fecha_evento ASC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $eventos = [];
    while ($row = $result->fetch_assoc()) {
        $eventos[] = $row;
    }
    echo json_encode(['success' => true, 'eventos' => $eventos]);
    exit;
}

// 2. PUT: ACTUALIZAR MIS DATOS
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $nombre = trim($data['nombre'] ?? '');
    $dni = trim($data['dni'] ?? '');
    $email = trim($data['email'] ?? '');
    $direccion = trim($data['direccion'] ?? '');

    $sql = "UPDATE usuarios SET nombre=?, dni=?, email=?, direccion=? WHERE id=?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ssssi", $nombre, $dni, $email, $direccion, $user_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Perfil actualizado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
    }
    exit;
}

// 3. PATCH: SOLICITAR SER TXANDALARI
if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    $sql = "UPDATE usuarios SET solicitud_txandalari = 1 WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) echo json_encode(['success' => true, 'message' => 'Solicitud enviada']);
    exit;
}

// 4. DELETE: ELIMINAR CUENTA
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    // --- SEGURIDAD: Evitar que un admin se borre a sí mismo ---
    $rol_usuario = $_SESSION['rol'] ?? '';
    if ($rol_usuario === 'admin') {
        echo json_encode(['success' => false, 'message' => 'Seguridad: Los administradores no pueden eliminar su propia cuenta desde la web.']);
        exit;
    }
    // ----------------------------------------------------------

    // Borrar turnos del usuario
    $stmt = $mysqli->prepare("DELETE FROM turnos WHERE id_usuario = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Borrar asistencias del usuario
    $stmt = $mysqli->prepare("DELETE FROM asistencias WHERE id_usuario = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Finalmente borrar usuario
    $stmt = $mysqli->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Usuario eliminado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario']);
    }

    exit;
}

cerrarConexion($mysqli);
?>