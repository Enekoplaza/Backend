<?php
session_start();
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");

// --- LAS 3 LÍNEAS SALVAVIDAS CONTRA EL BLOQUEO DE CORS ---
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
// --------------------------------------------------------

header("Content-Type: application/json; charset=UTF-8");

// Destruimos la sesión en el servidor
session_destroy();

echo json_encode(['success' => true]);
?>