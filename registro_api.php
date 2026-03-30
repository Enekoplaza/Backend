<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true"); 
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once "usuarios.php";

$data = json_decode(file_get_contents('php://input'), true);

$nombre    = trim($data['nombre'] ?? '');
$dni       = trim($data['dni'] ?? '');
$email     = trim($data['email'] ?? '');
$password  = $data['password'] ?? '';
$direccion = trim($data['direccion'] ?? '');
$rol       = $data['rol'] ?? 'socio';

// --- NUEVAS VALIDACIONES DE BACKEND ---
if (strlen($nombre) < 3) {
    echo json_encode(['success' => false, 'message' => 'Seguridad: El nombre debe tener al menos 3 caracteres']);
    exit;
}

// PHP tiene una función nativa buenísima para validar emails
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Seguridad: Formato de email inválido']);
    exit;
}

if (strlen($password) < 5) {
    echo json_encode(['success' => false, 'message' => 'Seguridad: La contraseña debe tener al menos 5 caracteres']);
    exit;
}
// --- FIN VALIDACIONES ---

if (!$nombre || !$dni || !$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios']);
    exit;
}

$res = registroUsuario($nombre, $dni, $email, $password, $direccion, $rol);

if ($res === true) {
    echo json_encode(['success' => true, 'message' => 'Usuario creado con éxito']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error: El email o DNI ya existen en nuestra base de datos']);
}
?>
