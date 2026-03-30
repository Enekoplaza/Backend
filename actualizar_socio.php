<?php
header('Content-Type: application/json');
session_start();

// Desactivamos warnings/notices para no romper el JSON
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Recibimos los datos
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$id = intval($data['id']); // aseguramos que sea entero
$nombre = $data['nombre'] ?? '';
$email = $data['email'] ?? '';
$dni = $data['dni'] ?? '';
$direccion = $data['direccion'] ?? '';

// Conexión a la base de datos
$conn = new mysqli("localhost", "root", "", "tu_bd");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => $conn->connect_error]);
    exit;
}

// Preparar y ejecutar la actualización
$stmt = $conn->prepare("UPDATE socios SET nombre=?, email=?, dni=?, direccion=? WHERE id=?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => $conn->error]);
    exit;
}
$stmt->bind_param("ssssi", $nombre, $email, $dni, $direccion, $id);

if($stmt->execute()) {
    // Actualizamos la sesión si el usuario que se modifica es el logeado
    if (isset($_SESSION['usuario']) && $_SESSION['usuario']['id'] == $id) {
        $_SESSION['usuario']['nombre'] = $nombre;
        $_SESSION['usuario']['email'] = $email;
        $_SESSION['usuario']['dni'] = $dni;
        $_SESSION['usuario']['direccion'] = $direccion;
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
exit; // Evita que PHP agregue HTML al final
?>