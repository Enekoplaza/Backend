<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require 'db.php'; // tu conexión a la base de datos

$user_id = $_GET['user_id'] ?? 0;
if (!$user_id) { echo json_encode(['eventos'=>[]]); exit; }

$stmt = $db->prepare("SELECT id, nombre, fecha FROM eventos WHERE id IN (SELECT evento_id FROM inscripciones WHERE user_id = ?) ORDER BY fecha DESC");
$stmt->execute([$user_id]);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['eventos'=>$eventos]);
?>