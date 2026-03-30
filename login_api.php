<?php
session_start();
//header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once "usuarios.php";

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? null;
$password = $data['password'] ?? null;

$user = login($email, $password);

if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['rol'] = $user['rol'];
    
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? null;
$password = $data['password'] ?? null;

$user = login($email, $password);

if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['rol'] = $user['rol'];
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'rol' => $user['rol']
        ]
        'user' => [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'rol' => $user['rol']
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
    echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
}
?>
