<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once "conexion.php";

$data = json_decode(file_get_contents('php://input'), true);

$nombre_artista = trim($data['nombre_artista'] ?? '');
$email_contacto = trim($data['email_contacto'] ?? '');
$descripcion    = trim($data['descripcion'] ?? '');

if (!$nombre_artista || !$email_contacto || !$descripcion) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit;
}

if (!filter_var($email_contacto, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email no válido']);
    exit;
}

$mysqli = conexionBBDD();
// 'estado' y 'fecha_solicitud' se ponen solos por tu diseño de BD
$sql = "INSERT INTO solicitudes_artistas (nombre_artista, email_contacto, descripcion) VALUES (?, ?, ?)";
$stmt = $mysqli->prepare($sql);

if ($stmt) {
    $stmt->bind_param("sss", $nombre_artista, $email_contacto, $descripcion);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Solicitud enviada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar la solicitud']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
}

cerrarConexion($mysqli);
?>