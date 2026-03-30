<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? 0;

if ($user_id) {
    // Puedes crear una tabla de solicitudes de txandalari
    $stmt = $db->prepare("INSERT INTO solicitudes_txandalari (user_id, fecha) VALUES (?, NOW())");
    $ok = $stmt->execute([$user_id]);
    echo json_encode(['ok'=>$ok]);
} else {
    echo json_encode(['ok'=>false]);
}
?>