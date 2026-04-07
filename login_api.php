<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once "usuarios.php";

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? null;
$password = $data['password'] ?? null;

$user = login($email, $password);

if ($user) {
    // 1. GUARDAR EN LA SESIÓN (Para que check_sesion.php los encuentre después)
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nombre'] = $user['nombre'];
    $_SESSION['rol'] = $user['rol'];
    $_SESSION['dni'] = $user['dni'];           // <--- NUEVO
    $_SESSION['email'] = $user['email'];       // <--- NUEVO
    $_SESSION['direccion'] = $user['direccion']; // <--- NUEVO
    $_SESSION['solicitud_txandalari'] = $user['solicitud_txandalari'] ?? 0;

    // 2. ENVIAR AL FRONTEND (Para que el login funcione al instante)
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'rol' => $user['rol'],
            'dni' => $user['dni'],           // <--- NUEVO
            'email' => $user['email'],       // <--- NUEVO
            'direccion' => $user['direccion'], // <--- NUEVO
            'solicitudTxandalari' => $user['solicitud_txandalari'] ?? 0
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
}
?>