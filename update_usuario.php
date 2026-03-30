<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? 0;
$nombre = $data['nombre'] ?? '';
$email = $data['email'] ?? '';
$telefono = $data['telefono'] ?? '';

if ($user_id) {
    $stmt = $db->prepare("UPDATE usuarios SET nombre=?, email=?, telefono=? WHERE id=?");
    $ok = $stmt->execute([$nombre, $email, $telefono, $user_id]);
    echo json_encode(['ok'=>$ok]);
} else {
    echo json_encode(['ok'=>false]);
}
?>