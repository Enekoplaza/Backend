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
    // 1. GUARDAR EN LA SESIÓN
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nombre'] = $user['nombre'];
    $_SESSION['rol'] = $user['rol'];
    $_SESSION['dni'] = $user['dni'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['direccion'] = $user['direccion'];
    $_SESSION['solicitud_txandalari'] = $user['solicitud_txandalari'] ?? 0;
    $_SESSION['qr_token'] = $user['qr_token']; // <--- NUEVO

    // 2. ENVIAR AL FRONTEND
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'rol' => $user['rol'],
            'dni' => $user['dni'],
            'email' => $user['email'],
            'direccion' => $user['direccion'],
            'solicitudTxandalari' => $user['solicitud_txandalari'] ?? 0,
            'qr_token' => $user['qr_token'] // <--- SE ENVÍA EL TOKEN AL HACER LOGIN
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
}
?>