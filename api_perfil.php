<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

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

// 4. POST: ASIGNAR TURNO (CORREGIDO PARA NO DUPLICAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['accion']) && $data['accion'] === 'asignar_turno') {
        $id_evento = $data['id_evento'];
        $puesto = $data['puesto']; // Puede estar vacío
        
        if ($puesto) {
            // 1º Comprobamos si el usuario ya tiene algún puesto asignado en este evento
            $check_sql = "SELECT id FROM turnos WHERE id_evento = ? AND id_usuario = ?";
            $stmt_check = $mysqli->prepare($check_sql);
            $stmt_check->bind_param("ii", $id_evento, $user_id);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result();

            if ($res_check->num_rows > 0) {
                // Si ya existe, simplemente ACTUALIZAMOS la palabra (ej: de 'barra' a 'puerta')
                $update_sql = "UPDATE turnos SET puesto = ? WHERE id_evento = ? AND id_usuario = ?";
                $stmt_upd = $mysqli->prepare($update_sql);
                $stmt_upd->bind_param("sii", $puesto, $id_evento, $user_id);
                $stmt_upd->execute();
            } else {
                // Si NO existe, CREAMOS un registro nuevo
                $insert_sql = "INSERT INTO turnos (id_evento, id_usuario, puesto) VALUES (?, ?, ?)";
                $stmt_ins = $mysqli->prepare($insert_sql);
                $stmt_ins->bind_param("iis", $id_evento, $user_id, $puesto);
                $stmt_ins->execute();
            }
            echo json_encode(['success' => true]);
        } else {
            // Si elige "Sin puesto", lo borramos de la tabla turnos
            $sql = "DELETE FROM turnos WHERE id_evento = ? AND id_usuario = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ii", $id_evento, $user_id);
            if ($stmt->execute()) echo json_encode(['success' => true]);
        }
        exit;
    }
}
 // 5. DELETE: ELIMINAR CUENTA
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {

    // Borramos primero datos relacionados (opcional pero recomendable)
    
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