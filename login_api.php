<?php
session_start();

// En lugar de una URL fija, permitimos que sea dinámica o la configuramos según el entorno
header("Access-Control-Allow-Origin: http://localhost:5173"); 
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

header("Content-Type: application/json; charset=UTF-8");

require_once "usuarios.php";

// LEER EL JSON (Esto ya lo tenías bien, es el "apartado" clave)
$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'] ?? null;
$password = $data['password'] ?? null;

$user = login($email, $password);

if ($user) {
    // GUARDAR EN LA SESIÓN PHP
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nombre'] = $user['nombre'];
    $_SESSION['rol'] = $user['rol'];
    // ... resto de tus asignaciones de sesión ...

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'rol' => $user['rol'],
            'email' => $user['email'],
            // Asegúrate de que las claves coincidan con lo que espera tu Vue
            'solicitudTxandalari' => $user['solicitud_txandalari'] ?? 0,
            'qr_token' => $user['qr_token']
        ]
    ]);
} else {
    http_response_code(401); // Enviamos un código de error de "No autorizado"
    echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
}
?>